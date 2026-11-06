<?php
namespace Zero1\LayoutXmlPlus\Model\Processor;

use DOMDocument;
use Zero1\LayoutXmlPlus\Model\AbstractProcessor;

class ChildHtml extends AbstractProcessor
{
    /** @var ScriptMasker */
    protected $scriptMasker;

    /**
     * @param Sanitizer $sanitizer
     * @param ScriptMasker|null $scriptMasker
     */
    public function __construct(
        Sanitizer $sanitizer,
        ?ScriptMasker $scriptMasker = null
    ) {
        parent::__construct($sanitizer);
        $this->scriptMasker = $scriptMasker ?? new ScriptMasker();
    }

    public const TARGET_START = 'start';
    public const TARGET_BEFORE = 'before';
    public const TARGET_END = 'end';
    public const TARGET_AFTER = 'after';
    public const TARGET_REPLACE = 'replace';

    /**
     * @param \DOMElement $node
     * @param \DOMDocument $dom
     * @param array<mixed> $options
     * @param \Magento\Framework\View\Element\AbstractBlock $block
     * @return void
     */
    public function process($node, $dom, $options, $block)
    {
        if(!isset($options['block'])){
            throw new \InvalidArgumentException('"block" option missing');
        }
        if(!isset($options['target'])){
            throw new \InvalidArgumentException('"target" option missing');
        }
        $target = $options['target'];
        $childId = $options['block'];

        if(!$block->getChildBlock($childId)){
            throw new \InvalidArgumentException('Unable to find child block "'.$childId.'"');
        }

        $childHtml = $block->getChildHtml($childId);
        if(!$childHtml){
            return;
        }

        // Parse the child html the same way as the parent output: masked <script>/<style> bodies (restored by the
        // parent's final unmask), an explicit utf-8 charset (no mojibake) and a <root> wrapper so ALL top level nodes,
        // including bare text, are kept - not just the first element.
        $contentTypeMeta = '<meta http-equiv="Content-Type" content="text/html; charset=utf-8">';
        $childHtml = $this->sanitize(
            '<root>' . $contentTypeMeta . $this->scriptMasker->mask($childHtml) . '</root>'
        );
        $childDom = new DOMDocument();
        $previous = libxml_use_internal_errors(true);
        $childDom->loadHTML($childHtml, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
        libxml_clear_errors();
        libxml_use_internal_errors($previous);

        $domNode = $dom->createDocumentFragment();
        $childRoot = $childDom->getElementsByTagName('root')->item(0);
        foreach ($childRoot ? iterator_to_array($childRoot->childNodes) : [] as $childNode) {
            if ($childNode instanceof \DOMElement && strtolower($childNode->nodeName) === 'meta'
                && $childNode->getAttribute('http-equiv')
            ) {
                continue;
            }
            $domNode->appendChild($dom->importNode($childNode, true));
        }
        if (!$domNode->hasChildNodes()) {
            return;
        }

        switch($target){
            case self::TARGET_START:
                $node->insertBefore(
                    $domNode,
                    $node->firstChild
                );
                break;
            case self::TARGET_BEFORE:
                $node->parentNode->insertBefore(
                    $domNode,
                    $node
                );
                break;
            case self::TARGET_END:
                $node->appendChild(
                    $domNode
                );
                break;
            case self::TARGET_AFTER:
                if($node->nextSibling === null){
                    $node->parentNode->appendChild(
                        $domNode
                    );
                }else{
                    $node->parentNode->insertBefore(
                        $domNode,
                        $node->nextSibling
                    );
                }
                break;
            case self::TARGET_REPLACE:
                $node->parentNode->insertBefore(
                    $domNode,
                    $node
                );
                $node->parentNode->removeChild($node);
                break;
            default:
                throw new \InvalidArgumentException('Invalid target: "'.$target.'"');
        }
    }
}