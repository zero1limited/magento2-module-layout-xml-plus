<?php

declare(strict_types=1);

namespace Zero1\LayoutXmlPlus\Model\Config\Source;

class CollectStatus implements \Magento\Framework\Option\ArrayInterface
{
    public const STATUS_WITH_THEME = 1;
    public const STATUS_WITHOUT_THEME = 2;
    public const STATUS_DISABLED = 0;

    /**
     * Options getter
     *
     * @return array
     */
    public function toOptionArray()
    {
        return [
            ['value' => self::STATUS_WITHOUT_THEME, 'label' => __('Without Theme')], 
            ['value' => self::STATUS_WITH_THEME, 'label' => __('With Theme')], 
            ['value' => self::STATUS_DISABLED, 'label' => __('Disabled')]
        ];
    }

    /**
     * Get options in "key-value" format
     *
     * @return array
     */
    public function toArray()
    {
        return [
            self::STATUS_WITHOUT_THEME => __('Without Theme'), 
            self::STATUS_WITH_THEME => __('With Theme'), 
            self::STATUS_DISABLED => __('Disabled'), 
        ];
    }
}
