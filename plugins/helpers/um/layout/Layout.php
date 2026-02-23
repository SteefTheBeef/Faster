<?php

require_once __DIR__ . '/LayoutGeometry.php';
require_once __DIR__ . '/LayoutTheme.php';
require_once __DIR__ . '/LayoutMarkup.php';

class Layout
{
    /** @var LayoutGeometry */
    public $geometry;

    /** @var LayoutTheme */
    public $theme;

    /** @var LayoutMarkup */
    public $markup;

    public static function build()
    {
        $layout = new self();

        $layout->geometry = LayoutGeometry::buildDefault();
        $layout->theme    = LayoutTheme::buildDefault();
        $layout->markup   = LayoutMarkup::from($layout->geometry, $layout->theme);

        return $layout;
    }
}