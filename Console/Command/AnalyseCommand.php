<?php

declare(strict_types=1);

namespace Zero1\LayoutXmlPlus\Console\Command;

use ArrayIterator;
use Magento\Framework\Exception\LocalizedException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Output\OutputInterface;
use DOMDocument;
use DOMNode;
use DOMXPath;
use Mustache_Engine;
use Mustache_Loader_FilesystemLoader;
use Mustache_Logger_StreamLogger;
use Psr\Log\LoggerInterface;
use Zero1\LayoutXmlPlus\Model\Processor\Sanitizer;
use Zero1\LayoutXmlPlus\Model\Config;
use Zero1\LayoutXmlPlus\Model\Analyser;

/**
 * @see https://developer.adobe.com/commerce/php/development/cli-commands/custom/
 */ 
class AnalyseCommand extends Command
{
    public const DIFF_ATTRIBUTE_CHANGE = 1;
    public const DIFF_ATTRIBUTE_REMOVED = 1;
    public const DIFF_ATTRIBUTUE_ADDED = 1;

    public const DIFF_VALUE_CHANGED = 10;
    public const DIFF_TEXT_CONTENT_CHANGED = 10;

    public const DIFF_TAG_CHANGED = 100;
    public const DIFF_NAME_CHANGED = 100;
    public const DIFF_TYPE_CHANGED = 100;

    public const DIFF_NODE_REMOVED = 1000;
    public const DIFF_NODE_ADDED = 1000;

    protected Sanitizer $sanitizer;

    protected Analyser $analyser;

    public function __construct(
        Sanitizer $sanitizer,
        Analyser $analyser
    ){
        $this->sanitizer = $sanitizer;
        $this->analyser = $analyser;
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->setName('dev:layout-xml-plus:analyse');
        $this->setDescription('Analyse captured files');
        parent::configure();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $analysis = $this->analyser->analyse();

        print_r(array_keys($analysis));
        
        $firstKey = array_key_first($analysis['diffs']);
        print_r(array_keys($analysis['diffs'][$firstKey]));

        $analysis['diffs'] = new ArrayIterator($analysis['diffs']);

        echo realpath(__DIR__.'/../../var/report/template').PHP_EOL;

        $mustache = new Mustache_Engine([
            'loader' => new Mustache_Loader_FilesystemLoader(realpath(__DIR__.'/../../var/report/template')),
            'partials_loader' => new Mustache_Loader_FilesystemLoader(realpath(__DIR__.'/../../var/report/partial')),
            'logger' => new Mustache_Logger_StreamLogger('php://stderr'),
            // 'entity_flags' => ENT_QUOTES
        ]);

        $template = $mustache->loadTemplate('report');
        echo $template->render($analysis).PHP_EOL;

        file_put_contents('pub/foo.html', $template->render($analysis));

        return 0;
    }

    /**
     * Execute the command
     *
     * @param InputInterface $input
     * @param OutputInterface $output
     *
     * @return int
     */
    protected function zexecute(InputInterface $input, OutputInterface $output): int
    {
        $withTheme = 'var/layout-xml/foo';
        $withoutTheme = 'var/layout-xml/bar';

        $withThemeData = json_decode(
            file_get_contents($withTheme.'/data.json'),
            true
        );

        $withoutThemeData = json_decode(
            file_get_contents($withoutTheme.'/data.json'),
            true
        );

        ksort($withThemeData);
        ksort($withoutThemeData);

        $commonFiles = array_intersect(array_keys($withThemeData), array_keys($withoutThemeData));

        $filesToCompare = [];
        foreach($commonFiles as $commonFile){
            if($withThemeData[$commonFile] != $withoutThemeData[$commonFile]){
                $filesToCompare[] = $commonFile;
            }
        }

        echo 'Total files: '.count($commonFiles).PHP_EOL;
        echo 'Files to compare: '.count($filesToCompare).PHP_EOL;

        $scored = [];

        foreach($filesToCompare as $fileToCompare){

            $withThemeFilepath = $withTheme.'/outputs/'.str_replace('/', '-', $fileToCompare);
            $withoutThemeFilepath = $withoutTheme.'/outputs/'.str_replace('/', '-', $fileToCompare);
            $withThemeContent = trim(file_get_contents($withThemeFilepath));
            $withoutThemeContent = trim(file_get_contents($withoutThemeFilepath));

            $cmd = 'diff -u '.$withoutThemeFilepath.' '.$withThemeFilepath;
            // echo $cmd.PHP_EOL;

            $scored[$fileToCompare] = $this->compareDom($withoutThemeContent, $withThemeContent);
            $scored[$fileToCompare]['diff_output_cmd'] = 'diff -u '.$withoutThemeFilepath.' '.$withThemeFilepath;
            $scored[$fileToCompare]['diff_output'] = shell_exec($scored[$fileToCompare]['diff_output_cmd']);
            $scored[$fileToCompare]['diff_input_cmd'] = 'diff -u '.$withoutThemeData[$fileToCompare].' '.$withThemeData[$fileToCompare];
            $scored[$fileToCompare]['diff_input'] = shell_exec($scored[$fileToCompare]['diff_input_cmd']);
        }

        uasort($scored, function($a, $b){
            if($a['score'] > $b['score']){
                return 1;
            }
            if($a['score'] < $b['score']){
                return -1;
            }
            return 0;
        });

        foreach($scored as $template => $scoreInfo){
            echo $template.' '.
                'score: '.$scoreInfo['score']
                .', '.$scoreInfo['modification_count'].' modifications'.
                ', '.count($scoreInfo['added']).' node added'.
                ', '.count($scoreInfo['removed']).' node removed'.PHP_EOL;
                
            echo '    '.$scoreInfo['diff_output'].PHP_EOL;
            echo '    '.$scoreInfo['diff_input'].PHP_EOL;
            echo PHP_EOL;
        }
        
        return 0;
    }

    protected function compareDom($fromContent, $toContent)
    {
        if($fromContent == $toContent){
            return [
                'score' => 0,
                'modification_count' => 0,
                'added' => [],
                'removed' => [],
                'modified' => [],
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

        foreach($flatterenedFromArray as $fromPath => $fromNodeArray){
            if(!isset($flatterenedToArray[$fromPath])){
                $removed[$fromPath] = 1;
                $score += self::DIFF_NODE_REMOVED;
            }else{
                $diffs = $this->compare($fromNodeArray, $flatterenedToArray[$fromPath]);
                if(!empty($diffs)){
                    $modified[$fromPath] = $diffs;
                    $modificationCount += count($diffs);
                    foreach($diffs as $d){
                        switch(0){
                            case strpos($d, 'tag'):
                                $score += self::DIFF_TAG_CHANGED;
                                break;
                            case strpos($d, 'name'):
                                $score += self::DIFF_NAME_CHANGED;
                                break;
                            case strpos($d, 'type'):
                                $score += self::DIFF_TYPE_CHANGED;
                                break;
                            case strpos($d, 'value'):
                                $score += self::DIFF_VALUE_CHANGED;
                                break;
                            case strpos($d, 'text_content'):
                                $score += self::DIFF_TEXT_CONTENT_CHANGED;
                                break;
                            case strpos($d, 'Attribute Removed'):
                                $score += self::DIFF_ATTRIBUTE_REMOVED;
                                break;
                            case strpos($d, 'Attribute Modified'):
                                $score += self::DIFF_ATTRIBUTE_REMOVED;
                                break;
                            case strpos($d, 'Attribute Added'):
                                $score += self::DIFF_ATTRIBUTUE_ADDED;
                                break;
                        }
                    }
                }
            }
        }
        foreach($flatterenedToArray as $toPath => $toNodeArray){
            if(!isset($flatterenedFromArray[$toPath])){
                $added[$toPath] = 1;
                $score += self::DIFF_NODE_ADDED;
            }
        }

        // echo 'score: '.$score.', '.$modificationCount.' modifications, '.count($added).' node added, '.count($removed).' node removed'.PHP_EOL;

        return [
            'score' => $score,
            'modification_count' => $modificationCount,
            'added' => $added,
            'removed' => $removed,
            'modified' => $modified,
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
