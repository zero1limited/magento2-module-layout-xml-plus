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
        // Escaper::escapeHtmlAttr() encodes spaces as "&#x20;". Decode them first (identical html), otherwise the "&"
        // replacement below would turn e.g. a whole class attribute into one opaque token and class xpaths/actions
        // would silently not match.
        $value = preg_replace('/&#(?:x0*20|0*32);/i', ' ', (string)$value);

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