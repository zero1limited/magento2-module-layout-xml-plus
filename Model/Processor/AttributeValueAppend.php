<?php
namespace Zero1\LayoutXmlPlus\Model\Processor;

use Zero1\LayoutXmlPlus\Model\AbstractProcessor;

class AttributeValueAppend extends AbstractProcessor
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
        if(!isset($options['value'])){
            throw new \InvalidArgumentException('"value" option missing');
        }

        $attribute = $this->sanitize($options['attribute']);
        $value = $options['value'];
        if(!is_array($value)){
            $value = [$value];
        }
        foreach($value as $v){
            $node->setAttribute(
                $attribute,
                $node->getAttribute($attribute).' '.$v
            );
        }
        $node->setAttribute(
            $attribute,
            trim(str_replace('  ', ' ', $node->getAttribute($attribute)))
        );
    }
}