<?php

/**
 * Tiny helper to build Manialink XML tags safely.
 * Keeps escaping centralized and avoids string-concatenation soup everywhere.
 */
class XmlTag {
    /**
     * @param string $tagName
     * @param array $attrs
     * @param string $innerXml If non-empty => <tag ...>inner</tag>, else <tag .../>
     * @return string
     */
    public static function tag($tagName, array $attrs, $innerXml = '') {
        $tagName = (string)$tagName;

        $attrXml = '';
        foreach ($attrs as $k => $v) {
            if ($v === null) {
                continue;
            }
            $k = (string)$k;
            $v = self::escapeAttr($v);
            $attrXml .= " {$k}='{$v}'";
        }

        if ($innerXml !== '') {
            return "<{$tagName}{$attrXml}>{$innerXml}</{$tagName}>";
        }

        return "<{$tagName}{$attrXml}/>";
    }

    /**
     * Convenience: build a quad using geometry + bgcolor, with sensible defaults.
     */
    public static function quad($x, $y, $width, $height, $bgColor, array $attrs = array()) {
        self::setCommonAttrs($attrs, $x, $y);

        $attrs['bgcolor'] = $bgColor;
        $attrs['sizen'] = "{$width} {$height}";

        return self::tag('quad', $attrs);
    }

    /**
     * Convenience: build a quad when you're using style/substyle instead of bgcolor.
     * (Icons, UI sprites, etc.)
     */
    public static function quadIcon64($x, $y, $size, $substyle, $action, array $attrs = array()) {
        if ($substyle === '') {
            return '';
        }

        self::setCommonAttrs($attrs, $x, $y);

        if (!isset($attrs['style'])) $attrs['style'] = 'Icons64x64_1';
        if (!isset($attrs['substyle'])) $attrs['substyle'] = $substyle;

        $attrs['sizen'] = "{$size} {$size}";
        $attrs['action'] = (int)$action;

        return self::tag('quad', $attrs);
    }

    /**
     * Convenience: build a label using geometry + text, with sensible defaults.
     *
     * Common overrides via $attrs:
     * - 'z' (number) extracted into posn third component
     * - 'halign', 'valign' (default: left/top)
     * - 'textsize' (default: 1)
     * - 'autonewline' (default: 1)
     * - any other label attributes supported by the target XML
     */
    public static function label($x, $y, $width, $height, $text, array $attrs = array()) {
        self::setCommonAttrs($attrs, $x, $y);

        $attrs['textsize'] = isset($attrs['textsize']) ? $attrs['textsize'] : 1;
        $attrs['autonewline'] = isset($attrs['autonewline']) ? $attrs['autonewline'] : 1;

        $attrs['sizen'] = "{$width} {$height}";
        $attrs['text'] = $text;

        return self::tag('label', $attrs);
    }

    public static function frame(array $attrs, $innerXml) {
        return self::tag('frame', $attrs, $innerXml);
    }

    private static function setCommonAttrs(array &$attrs, $x, $y) {
        $zIndex = self::popAttr($attrs, 'z', 0);

        $attrs['halign'] = self::getAttr($attrs, 'halign', 'left');
        $attrs['valign'] = self::getAttr($attrs, 'valign', 'top');

        $attrs['posn'] = "{$x} {$y} {$zIndex}";
    }

    private static function getAttr(array $attrs, $key, $default) {
        return isset($attrs[$key]) ? $attrs[$key] : $default;
    }

    private static function popAttr(array &$attrs, $key, $default) {
        $value = isset($attrs[$key]) ? $attrs[$key] : $default;
        unset($attrs[$key]);
        return $value;
    }

    private static function escapeAttr($value) {
        $value = (string)$value;
        $value = str_replace(array("\r", "\n", "\t"), ' ', $value);
        return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
    }

}
