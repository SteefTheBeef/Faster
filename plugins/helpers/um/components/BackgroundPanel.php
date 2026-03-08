<?php

class BackgroundPanel {
    public static function right(Layout $layout) {
        $panelW = $layout->geometry->panelWidth;
        $panelH = $layout->geometry->panelHeight;
        return XmlTag::quad(0, 0, $panelW, $panelH, $layout->theme->tabActiveBackgroundColor);
    }

    public static function left(Layout $layout) {
        $panelW = $layout->geometry->playerWidth;
        $panelH = $layout->geometry->panelHeight;
        return XmlTag::quad(0, 0, $panelW, $panelH, $layout->theme->panelBackgroundColor);
    }
}