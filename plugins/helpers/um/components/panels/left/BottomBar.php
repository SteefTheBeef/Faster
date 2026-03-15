<?php
class BottomBar {
    public static function render(UmPanelRenderContext $ctx) {
        $geometry = $ctx->layout->geometry;
        $theme = $ctx->layout->theme;

        return XmlTag::quad(-0.1, -$geometry->panelHeight - 0.1, $geometry->backgroundWidth + 0.2, 2.9, $theme->tabBackgroundColor);
    }
}