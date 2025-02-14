<?php

declare(strict_types=1);

namespace Zero1\LayoutXmlPlus\Model;

use Zero1\LayoutXmlPlus\Model\Config;
use Magento\Framework\Filesystem\DirectoryList;
use Magento\Framework\Filesystem\Io\File as IO;
use Zero1\LayoutXmlPlus\Model\Config\Source\CollectStatus;
use Zero1\LayoutXmlPlus\Model\Collector;
use DOMDocument;
use DOMNode;
use DOMXPath;
use Psr\Log\LoggerInterface;
use Zero1\LayoutXmlPlus\Model\Processor\Sanitizer;

class Analyser
{
    public const TYPE_ATTRIBUTE = 1;    // 00000001
    public const TYPE_CONTENT = 2;      // 00000010
    public const TYPE_OTHER = 4;        // 00000100
    public const TYPE_NODE = 8;         // 00001000

    public const DIFF_SCORE_ATTRIBUTE_CHANGE = 1;
    public const DIFF_SCORE_ATTRIBUTE_REMOVED = 1;
    public const DIFF_SCORE_ATTRIBUTUE_ADDED = 1;

    public const DIFF_SCORE_VALUE_CHANGED = 10;
    public const DIFF_SCORE_TEXT_CONTENT_CHANGED = 10;

    public const DIFF_SCORE_TAG_CHANGED = 100;
    public const DIFF_SCORE_NAME_CHANGED = 100;
    public const DIFF_SCORE_TYPE_CHANGED = 100;

    public const DIFF_SCORE_NODE_REMOVED = 1000;
    public const DIFF_SCORE_NODE_ADDED = 1000;

    protected Collector $collector;

    protected Sanitizer $sanitizer;

    public function __construct(
        Collector $collector,
        Sanitizer $sanitizer
    ){
        $this->collector = $collector;
        $this->sanitizer = $sanitizer;
    }

    public function analyse()
    {
        $analysis = [];

        $withThemeManifest = $this->collector->getWithThemeManifest();
        $withoutThemeManifest = $this->collector->getWithoutThemeManifest();

        $commonFiles = array_intersect(array_keys($withThemeManifest), array_keys($withoutThemeManifest));

        // find common files with different source template file
        $filesToCompare = [];
        foreach($commonFiles as $commonFile){
            if($withThemeManifest[$commonFile] != $withoutThemeManifest[$commonFile]){
                $filesToCompare[] = $commonFile;
            }
        }

        $analysis['total_files'] = count($commonFiles);
        $analysis['files_to_compare'] = count($filesToCompare);
        $analysis['no_difference'] = 0;
        $analysis['attribute_only_changes'] = 0;
        $analysis['diffs'] = [];

        foreach($filesToCompare as $fileToCompare){

            $withThemeSourceFile = $withThemeManifest[$fileToCompare];
            $withThemeOutputFile = $this->collector->getWithThemeOutputPath($fileToCompare);
            $withThemeOutput = $this->collector->getWithThemeOutput($fileToCompare);

            $withoutThemeSourceFile = $withoutThemeManifest[$fileToCompare];
            $withoutThemeOutputFile = $this->collector->getWithoutThemeOutputPath($fileToCompare);
            $withoutThemeOutput = $this->collector->getWithoutThemeOutput($fileToCompare);

            $diff = $this->compareDom(
                $withoutThemeOutput,
                $withThemeOutput
            );
            $diff['template_name'] = $fileToCompare;

            $diff['with_theme_source_path'] = $withThemeSourceFile;
            $diff['with_theme_output_path'] = $withThemeOutputFile;
            $diff['with_theme_output'] = $withThemeOutput;

            $diff['without_theme_source_path'] = $withoutThemeSourceFile;
            $diff['without_theme_output_path'] = $withoutThemeOutputFile;
            $diff['without_theme_output'] = $withoutThemeOutput;

            $diff['diff_output_cmd'] = 'diff -u '.$withoutThemeOutputFile.' '.$withThemeOutputFile;
            $diff['diff_output'] = shell_exec($diff['diff_output_cmd']);
            $diff['diff_input_cmd'] = 'diff -u '.$withoutThemeSourceFile.' '.$withThemeSourceFile;
            $diff['diff_input'] = shell_exec($diff['diff_input_cmd']);

            $analysis['diffs'][$fileToCompare] = $diff;
            if($diff['change_type'] === 0){
                $analysis['no_difference']++;
            }
            if($diff['change_type'] === self::TYPE_ATTRIBUTE){
                $analysis['attribute_only_changes']++;
            }
        }

        // sort
        uasort($analysis['diffs'], function($a, $b){
            if($a['score'] > $b['score']){
                return 1;
            }
            if($a['score'] < $b['score']){
                return -1;
            }
            return 0;
        });


        // return
        return $analysis;
    }

    protected function compareDom($fromContent, $toContent)
    {
        if($fromContent == $toContent){
            return [
                'score' => 0,
                'modification_count' => 0,
                'added' => [],
                'added_count' => 0,
                'removed' => [],
                'removed_count' => 0,
                'modified' => [],
                'modified_count' => 0,
                'change_type' => 0,
                'attribute_changes' => false,
                'content_changes' => false,
                'other_changes' => false,
                'node_changes' => false,
            ];
        }
        libxml_use_internal_errors(true);
        $fromDom = new DOMDocument();
        if($fromContent){
            $htmlToUse = $this->sanitizer->sanitize($fromContent);
            $fromDom->loadHTML($htmlToUse, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
        }
        libxml_clear_errors();
        $fromArray = $this->toArray($fromDom);
        $flatterenedFromArray = $this->flattern($fromArray);
        
        libxml_use_internal_errors(true);
        $toDom = new DOMDocument();
        if($toContent){
            $htmlToUse = $this->sanitizer->sanitize($toContent);
            $toDom->loadHTML($htmlToUse, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
        }
        libxml_clear_errors();
        $toArray = $this->toArray($toDom);
        $flatterenedToArray = $this->flattern($toArray);

        // echo 'from'.PHP_EOL;
        // print_r(array_keys($flatterenedFromArray));

        // echo 'to'.PHP_EOL;
        // print_r(array_keys($flatterenedToArray));


        $added = [];
        $removed = [];
        $modified = [];
        $modificationCount = 0;
        $score = 0;
        $changeType = 0;

        foreach($flatterenedFromArray as $fromPath => $fromNodeArray){
            if(!isset($flatterenedToArray[$fromPath])){
                $removed[$fromPath] = 1;
                $score += self::DIFF_SCORE_NODE_REMOVED;
                if(!($changeType & self::TYPE_NODE)){
                    $changeType += self::TYPE_NODE;
                }
            }else{
                $diffs = $this->compare($fromNodeArray, $flatterenedToArray[$fromPath]);
                if(!empty($diffs)){
                    $modified[$fromPath] = $diffs;
                    $modificationCount += count($diffs);
                    $type = null;
                    foreach($diffs as $d){
                        switch(0){
                            case strpos($d, 'tag'):
                                $score += self::DIFF_SCORE_TAG_CHANGED;
                                $type = self::TYPE_OTHER;
                                break;
                            case strpos($d, 'name'):
                                $score += self::DIFF_SCORE_NAME_CHANGED;
                                $type = self::TYPE_OTHER;
                                break;
                            case strpos($d, 'type'):
                                $score += self::DIFF_SCORE_TYPE_CHANGED;
                                $type = self::TYPE_OTHER;
                                break;
                            case strpos($d, 'value'):
                                $score += self::DIFF_SCORE_VALUE_CHANGED;
                                $type = self::TYPE_CONTENT;
                                break;
                            case strpos($d, 'text_content'):
                                $score += self::DIFF_SCORE_TEXT_CONTENT_CHANGED;
                                $type = self::TYPE_CONTENT;
                                break;
                            case strpos($d, 'Attribute Removed'):
                                $score += self::DIFF_SCORE_ATTRIBUTE_REMOVED;
                                $type = self::TYPE_ATTRIBUTE;
                                break;
                            case strpos($d, 'Attribute Modified'):
                                $score += self::DIFF_SCORE_ATTRIBUTE_REMOVED;
                                $type = self::TYPE_ATTRIBUTE;
                                break;
                            case strpos($d, 'Attribute Added'):
                                $score += self::DIFF_SCORE_ATTRIBUTUE_ADDED;
                                $type = self::TYPE_ATTRIBUTE;
                                break;
                        }
                    }
                    if($type !== null){
                        if(!($changeType & $type)){
                            $changeType += $type;
                        }
                    }
                }
            }
        }
        foreach($flatterenedToArray as $toPath => $toNodeArray){
            if(!isset($flatterenedFromArray[$toPath])){
                $added[$toPath] = 1;
                $score += self::DIFF_SCORE_NODE_ADDED;
                if(!($changeType & self::TYPE_NODE)){
                    $changeType += self::TYPE_NODE;
                }
            }
        }

        // echo 'score: '.$score.', '.$modificationCount.' modifications, '.count($added).' node added, '.count($removed).' node removed'.PHP_EOL;
        return [
            'score' => $score,
            'modification_count' => $modificationCount,
            'added' => $added,
            'added_count' => count($added),
            'removed' => $removed,
            'removed_count' => count($removed),
            'modified' => $modified,
            'modified_count' => count($modified),
            'change_type' => $changeType,
            'attribute_changes' => ($changeType & self::TYPE_ATTRIBUTE),
            'content_changes' => ($changeType & self::TYPE_CONTENT),
            'other_changes' => ($changeType & self::TYPE_OTHER),
            'node_changes' => ($changeType & self::TYPE_NODE),
        ];
    }

    protected function toArray(DOMNode $node, &$result = [])
    {
        // echo $node->getNodePath().PHP_EOL;
        $attributes = $node->attributes? iterator_to_array($node->attributes->getIterator()) : [];
        array_walk($attributes, function(\DOMAttr &$element){
            $element = [
                'name' => $element->name,
                'value' => $element->value,
            ];
        });
        $nodeArray = [
            'tag' => $node->tagName ?? '',
            'name' => $node->nodeName,
            'type' => $node->nodeType,
            'text_content' => trim($node->textContent),
            'value' => trim($node->nodeValue ?? ''),
            'attributes' => $attributes,
            'children' => [],
        ];
        foreach($node->childNodes as $child){
            $this->toArray($child, $nodeArray['children']);
        }
        $result[] = $nodeArray;
        return $result;
    }

    protected function flattern($array, &$path = [], &$result = [])
    {
        foreach($array as $k => $value){
            $path[] = $k;
            $path[] = $value['tag']? $value['tag'] : $value['name'];
            $result[implode('/', $path)] = $value;
            if(count($value['children'])){
                $this->flattern($value['children'], $path, $result);
            }
            array_pop($path);
            array_pop($path);
        }
        return $result;
    }

    protected function compare($from, $to)
    {
        $diff = [];

        foreach([
            'tag',
            'name',
            'type',
        ] as $key){
            if($from[$key] != $to[$key]){
                $diff[] = $key.' changed: '.$from[$key].' => '.$to[$key];
            }
        }

        foreach([
            'value',
            'text_content',
        ] as $key){
            if($from[$key] != $to[$key]){
                $diff[] = $key.' changed';
            }
        }

        foreach($from['attributes'] as $fromAttributeKey => $fromAttributeInfo){
            if(!isset($to['attributes'][$fromAttributeKey])){
                $diff[] = 'Attribute Removed '.$fromAttributeKey;
            }else{
                if($fromAttributeInfo['value'] != $to['attributes'][$fromAttributeKey]['value']){
                    $diff[] = 'Attribute Modified '.$fromAttributeKey;
                }
            }
        }
        foreach($to['attributes'] as $toAttributeKey => $toAttributeInfo){
            if(!isset($from['attributes'][$toAttributeKey])){
                $diff[] = 'Attribute Added '.$toAttributeKey;
            }
        }

        return $diff;
    }
}
