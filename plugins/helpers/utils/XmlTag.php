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
    public static function quad($x, $y, $width, $height, $bgColor, $action = null, array $attrs = array()) {
        self::setCommonAttrs($attrs, $x, $y, $action);

        $attrs['bgcolor'] = $bgColor;
        $attrs['sizen'] = "{$width} {$height}";

        return self::tag('quad', $attrs);
    }

    public static function quadCenterLeft($x, $y, $width, $height, $bgColor, $action = null, array $attrs = array()) {
        $attrs['valign'] = 'top';

        return self::quad($x, $y, $width, $height, $bgColor, $action, $attrs);
    }

    /**
     * Centralizes border quad creation to make intent obvious and avoid copy/paste bugs.
     *
     * @param float|int $x
     * @param float|int $y
     * @param float|int $w
     * @param float|int $h
     * @param string    $color
     * @return string
     */
    public static function quadBorder($x, $y, $w, $h, $color) {
        return self::quad($x, $y, $w, $h, $color);
    }
    /**
     * Convenience: build a quad when you're using style/substyle instead of bgcolor.
     * (Icons, UI sprites, etc.)
     */
    public static function quadIcon64($x, $y, $size, $substyle, $action = null, array $attrs = array()) {
        if ($substyle === '') {
            return '';
        }

        self::setCommonAttrs($attrs, $x, $y, $action);

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
    public static function label($x, $y, $width, $height, $text, $action = null, array $attrs = array()) {
        self::setCommonAttrs($attrs, $x, $y, $action);

        $attrs['textsize'] = isset($attrs['textsize']) ? $attrs['textsize'] : 1;
        $attrs['autonewline'] = isset($attrs['autonewline']) ? $attrs['autonewline'] : 1;

        $attrs['sizen'] = "{$width} {$height}";
        $attrs['text'] = $text;

        return self::tag('label', $attrs);
    }

    public static function labelRight($x, $y, $width, $height, $text, $action = null, array $attrs = array()) {
        $attrs['halign'] = 'right';

        return self::label($x, $y, $width, $height, $text, $action, $attrs);
    }
    public static function labelCenterLeft($x, $y, $width, $height, $text, $action = null, array $attrs = array()) {
        $attrs['valign'] = 'center';

        return self::label($x, $y, $width, $height, $text, $action, $attrs);
    }

    public static function labelCenterRight($x, $y, $width, $height, $text, $action = null, array $attrs = array()) {
        $attrs['valign'] = 'center';
        $attrs['halign'] = 'right';

        return self::label($x, $y, $width, $height, $text, $action, $attrs);
    }
    public static function labelCenterCenter($x, $y, $width, $height, $text, $action = null, array $attrs = array()) {
        $attrs['valign'] = 'center';
        $attrs['halign'] = 'center';

        return self::label($x, $y, $width, $height, $text, $action, $attrs);
    }

    public static function frame($x, $y, $z = 0, $innerXml = '') {
        $attrs = array('posn' => "{$x} {$y} {$z}");
        return self::tag('frame', $attrs, $innerXml);
    }

    /**
     * Generic prev/next pager (Icons64x64_1 ArrowPrev/ArrowNext + "page/pageCount" label)
     *
     * Important: x positions are computed relative to $containerW (typically table width),
     * so it keeps the "aligned to table width" behavior.
     *
     * @param float|int $frameX Frame origin X (where the pager frame is placed)
     * @param float|int $frameY Frame origin Y (where the pager frame is placed)
     * @param float|int $frameZ Frame origin Z
     * @param float|int $containerW Width used for internal alignment (typically table width)
     * @param int $page 0-based
     * @param int $pageCount total pages (>= 1)
     * @param int|null $prevAction Action id for prev (null/0 to disable)
     * @param int|null $nextAction Action id for next (null/0 to disable)
     * @param array $opts Optional overrides:
     *  - iconSize (float) default 1.6
     *  - labelW (float) default 2.0
     *  - gap (float) default 0.25
     *  - midY (float) default -0.8
     *  - textPrefix (string) default '$aaa'
     *  - prevSubstyle (string) default 'ArrowPrev'
     *  - nextSubstyle (string) default 'ArrowNext'
     * @return string
     */
    public static function pagerPrevNext64($frameX, $frameY, $frameZ, $containerW, $page, $pageCount, $prevAction, $nextAction, array $opts = array()) {
        $iconSize = isset($opts['iconSize']) ? (float)$opts['iconSize'] : 1.6;
        $labelW = isset($opts['labelW']) ? (float)$opts['labelW'] : 2.0;
        $gap = isset($opts['gap']) ? (float)$opts['gap'] : 0.25;
        $midY = isset($opts['midY']) ? (float)$opts['midY'] : -0.8;

        $textPrefix = isset($opts['textPrefix']) ? (string)$opts['textPrefix'] : '$aaa';
        $prevSubstyle = isset($opts['prevSubstyle']) ? (string)$opts['prevSubstyle'] : 'ArrowPrev';
        $nextSubstyle = isset($opts['nextSubstyle']) ? (string)$opts['nextSubstyle'] : 'ArrowNext';

        // NEW: horizontal alignment of the whole pager within containerW
        $align = isset($opts['align']) ? strtolower((string)$opts['align']) : 'right'; // 'left'|'center'|'right'
        $pagerW = (2.0 * $iconSize) + (2.0 * $gap) + $labelW;

        $containerW = (float)$containerW;
        if ($containerW < 0) $containerW = 0;

        $startX = 0.0;
        if ($align === 'left') {
            $startX = 0.0;
        } elseif ($align === 'center' || $align === 'middle') {
            $startX = ($containerW - $pagerW) / 2.0;
        } else { // default: right
            $startX = $containerW - $pagerW;
        }

        // Optional safety: clamp so we don't go negative if container is narrower than pager
        if ($startX < 0) $startX = 0.0;

        // Place elements relative to startX
        $prevX = $startX;
        $midX  = $startX + $iconSize + $gap + ($labelW / 2.0);
        $nextX = $startX + $iconSize + $gap + $labelW + $gap;

        $inner = '';
        $inner .= self::quadIcon64($prevX, 0, $iconSize, $prevSubstyle, $prevAction);
        $inner .= self::labelCenterCenter($midX, $midY, $labelW, $iconSize, $textPrefix . (((int)$page) + 1) . "/" . (int)$pageCount);
        $inner .= self::quadIcon64($nextX, 0, $iconSize, $nextSubstyle, $nextAction);

        return self::frame($frameX, $frameY, $frameZ, $inner);
    }

    private static function setCommonAttrs(array &$attrs, $x, $y, $action = null) {
        $zIndex = self::popAttr($attrs, 'z', 0);
        if ($action !== null) {
            $attrs['action'] = self::getAttr($attrs, 'action', $action);
        }

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
