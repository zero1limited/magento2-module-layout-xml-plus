<?php
namespace Zero1\LayoutXmlPlus\Model\Processor;

use Zero1\LayoutXmlPlus\Model\AbstractProcessor;

class AttributeValueReplace extends AbstractProcessor
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
        if(!isset($options['attribute'])){
            throw new \InvalidArgumentException('"attribute" option missing');
        }
        if(!isset($options['search'])){
            throw new \InvalidArgumentException('"search" option missing');
        }
        if(!isset($options['replace'])){
            throw new \InvalidArgumentException('"replace" option missing');
        }

        $attribute = $this->sanitize($options['attribute']);
        $search = $options['search'];
        $replace = $options['replace'];

        $node->setAttribute(
            $attribute,
            str_replace($search, $replace, $node->getAttribute($attribute))
        );
    }
}
