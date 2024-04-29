<?php
namespace Zero1\LayoutXmlPlus\Model\Processor;

use DOMDocument;
use Zero1\LayoutXmlPlus\Model\AbstractProcessor;

class ChildHtml extends AbstractProcessor
{
    public const TARGET_START = 'start';
    public const TARGET_BEFORE = 'before';
    public const TARGET_END = 'end';
    public const TARGET_AFTER = 'after';

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

        $childHtml = $this->sanitize($childHtml);
        $childDom = new DOMDocument();
        $childDom->loadHTML($childHtml, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
        $domNode = $dom->importNode($childDom->documentElement, true);

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
            default:
                throw new \InvalidArgumentException('Invalid target: "'.$target.'"');
        }
    }
}