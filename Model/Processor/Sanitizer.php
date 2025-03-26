<?php
namespace Zero1\LayoutXmlPlus\Model\Processor;

class Sanitizer
{
    public const SEARCH = [
        '@',
        '&',
        'esi:include',
        '£'
    ];

    public const REPLACE = [
        '___at___',
        '___amp___',
        'esi___include',
        '__pound__',
    ];

    public function sanitize($value)
    {
        return str_replace(
            self::SEARCH,
            self::REPLACE,
            $value
        );
    }

    public function unsanitize($value)
    {
        return str_replace(
            self::REPLACE,
            self::SEARCH,
            $value
        );
    }
}