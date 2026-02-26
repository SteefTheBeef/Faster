<?php
class BoardBorders {
    public static function render(Layout $layout) {
        $t = $layout->geometry->borderThickness;
        $c = $layout->theme->borderColor;

        $borderLeftX = $layout->geometry->borderLeftX;
        $borderRightX = isset($layout->geometry->borderRightX)
            ? $layout->geometry->borderRightX
            : ($layout->geometry->borderLeftX + $layout->geometry->borderOuterWidth - $t);

        $borderHeight = $layout->geometry->borderHeight;
        $listFrameY = $layout->geometry->listFrameY;

        $borderTopWidth = $layout->geometry->borderOuterWidth;
        $borderTopY = $layout->geometry->borderTopY;
        $borderBottomY = $layout->geometry->borderBottomY;

        $xml = XmlTag::quadBorder($borderLeftX, $listFrameY, $t, $borderHeight, $c); // left
        $xml .= XmlTag::quadBorder($borderLeftX, $borderTopY, $borderTopWidth, $t, $c); // top
        $xml .= XmlTag::quadBorder($borderLeftX, $borderBottomY, $borderTopWidth, $t, $c); // bottom
        $xml .= XmlTag::quadBorder($borderRightX, $listFrameY, $t, $borderHeight, $c); // right

        return $xml;
    }
}