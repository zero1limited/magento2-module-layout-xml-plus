<?php
namespace Zero1\LayoutXmlPlus\Model\Processor;

class Sanitizer
{
    public const TEMPLATE_AT = 'atatatat';

    public function sanitize($value)
    {
        $value = str_replace('@', self::TEMPLATE_AT, $value);
        return $value;
    }

    public function unsanitize($value)
    {
        $value = str_replace(self::TEMPLATE_AT, '@', $value);
        return $value;
    }
}

