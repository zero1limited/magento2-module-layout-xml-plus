<?php
namespace Zero1\LayoutXmlPlus\Model\Processor;

/**
 * Protects the bodies of <script> and <style> elements from DOMDocument::loadHTML().
 *
 * libxml's HTML parser ends a raw-text element at the first "</" followed by a letter, so inline JS containing
 * e.g. '<b>%s</b>' or `</span>` in a template literal gets truncated/corrupted. Bodies are swapped for opaque
 * tokens before parsing and restored after serialising.
 *
 * Tokens are unique per request and the store is static, so html masked by one instance (e.g. in child_html) is
 * restored by whichever instance unmasks the final output, and nested processing never clobbers a parent's tokens.
 */
class ScriptMasker
{
    private const TOKEN_PREFIX = '___lxp_raw_';
    private const TOKEN_SUFFIX = '___';

    /** @var array<string, string> */
    private static $bodies = [];

    /** @var int */
    private static $counter = 0;

    public function mask(string $html): string
    {
        $masked = preg_replace_callback(
            '#(<(script|style)\b[^>]*>)(.*?)(</\2\s*>)#is',
            function (array $m) {
                if ($m[3] === '') {
                    return $m[0];
                }
                $token = self::TOKEN_PREFIX . (++self::$counter) . self::TOKEN_SUFFIX;
                self::$bodies[$token] = $m[3];
                return $m[1] . $token . $m[4];
            },
            $html
        );

        // On a PCRE failure (e.g. backtrack limit on huge input) fall back to the unmasked html.
        return $masked === null ? $html : $masked;
    }

    public function unmask(string $html): string
    {
        if (!self::$bodies) {
            return $html;
        }

        return preg_replace_callback(
            '#' . preg_quote(self::TOKEN_PREFIX, '#') . '\d+' . preg_quote(self::TOKEN_SUFFIX, '#') . '#',
            function (array $m) {
                if (!array_key_exists($m[0], self::$bodies)) {
                    return $m[0];
                }
                $body = self::$bodies[$m[0]];
                unset(self::$bodies[$m[0]]);
                return $body;
            },
            $html
        ) ?? $html;
    }
}
