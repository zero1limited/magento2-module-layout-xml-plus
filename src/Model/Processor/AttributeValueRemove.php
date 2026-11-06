<?php
namespace Zero1\LayoutXmlPlus\Model\Processor;

use Zero1\LayoutXmlPlus\Model\AbstractProcessor;

class AttributeValueRemove extends AbstractProcessor
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
        // Remove whole whitespace separated tokens only, so removing "p-4" leaves "md:p-4" / "group-p-4" intact.
        $remove = array_map(function ($v) {
            return $this->sanitize((string)$v);
        }, $value);
        $tokens = preg_split('/\s+/', trim((string)$node->getAttribute($attribute)), -1, PREG_SPLIT_NO_EMPTY);
        $tokens = array_filter($tokens, function ($token) use ($remove) {
            return !in_array($token, $remove, true);
        });
        $node->setAttribute(
            $attribute,
            implode(' ', $tokens)
        );
    }
}