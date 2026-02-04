<?php
namespace Zero1\LayoutXmlPlus\Model\Processor;

use Zero1\LayoutXmlPlus\Model\AbstractProcessor;

class Remove extends AbstractProcessor
{
    /**
     * @param \DOMElement $node
     * @param \DOMDocument $dom
     * @param array<mixed> $options
     * @param \Magento\Framework\View\Element\AbstractBlock $block
     * @return void
     */
    public function process($node, $dom, $options, $block)
    {
        $node->parentNode->removeChild($node);
    }
}
