<?php

require_once "LayoutTheme.php";
require_once "LayoutMarkup.php";
require_once "LayoutGeometry.php";
require_once "LayoutGeometryMini.php";
require_once "LayoutGeometryMiniScoreBoard.php";

class Layout
{
    /** @var LayoutGeometry */
    public $geometry;

    /** @var LayoutGeometryMini */
    public $geometryMini;

    /** @var LayoutGeometryMiniScoreBoard */
    public $geometryMiniScoreBoard;

    /** @var LayoutTheme */
    public $theme;

    /** @var LayoutMarkup */
    public $markup;

    public static function build()
    {
        $layout = new self();

        $layout->geometry = LayoutGeometry::buildDefault();
        $layout->geometryMini = LayoutGeometryMini::buildDefault();
        $layout->geometryMiniScoreBoard = LayoutGeometryMiniScoreBoard::buildDefault();
        $layout->theme    = LayoutTheme::buildDefault();
        $layout->markup   = LayoutMarkup::from($layout->geometry, $layout->theme);

        return $layout;
    }
}