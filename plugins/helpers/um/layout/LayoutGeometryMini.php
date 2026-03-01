<?php

class LayoutGeometryMini
{
    // base/tunables
   public $mainFrameX;
   public $mainFrameY;
   public $width;
   public $height;

    public static function buildDefault()
    {
        $g = new self();

        $g->mainFrameX = 43;
        $g->mainFrameY = -30;
        $g->width = 22.0;
        $g->height = 10.0;
        return $g;
    }
}