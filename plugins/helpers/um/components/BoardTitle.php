<?php
class BoardTitle {
    public static function render(Layout $layout, $text) {
        $leftX = $layout->geometry->borderLeftX;
        $outerW = $layout->geometry->borderOuterWidth;
        $topY = $layout->geometry->borderTopY;

        $titleH = 3.0;
        $titleW = $outerW;
        $centerX = $leftX + ($outerW / 2.0);

        // Move this up/down to match your red line precisely:
        $y = $topY + 6.6;

        // If your layout theme has a font style you like, you can use it here.
        $font = $layout->theme->panelTitleFontStyle;

        return XmlTag::labelCenterCenter(
            $centerX,
            $y,
            $titleW,
            $titleH,
            $font . $text,
            null,
            array('textsize' => 3.2, 'z' => 0.8)
        );
    }
}
