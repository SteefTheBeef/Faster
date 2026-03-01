<?php

class Layout
{
    /** @var LayoutGeometry */
    public $geometry;

    /** @var LayoutGeometryMini */
    public $geometryMini;

    /** @var LayoutTheme */
    public $theme;

    /** @var LayoutMarkup */
    public $markup;

    public static function build()
    {
        $layout = new self();

        $layout->geometry = LayoutGeometry::buildDefault();
        $layout->geometryMini = LayoutGeometryMini::buildDefault();
        $layout->theme    = LayoutTheme::buildDefault();
        $layout->markup   = LayoutMarkup::from($layout->geometry, $layout->theme);

        return $layout;
    }
}