<?php

class LayoutGeometryMiniScoreBoard
{
    // base/tunables
   public $mainFrameX;
   public $mainFrameY;
   public $width;
   public $height;

    public static function buildDefault()
    {
        $g = new self();

        $g->mainFrameX = -65;
        $g->mainFrameY = 28;
        $g->width = 22.0;
        $g->height = 10.0;
        return $g;
    }
}