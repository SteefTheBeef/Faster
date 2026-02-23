<?php

class LayoutGeometry
{
    // base/tunables
    public $backgroundWidth;
    public $backgroundHeight;
    public $rowCount;

    public $gap;

    // derived geometry
    public $playerWidth;
    public $playerHeight;

    public $backgroundTopLeftX;
    public $backgroundTopLeftY;
    public $listFrameY;

    public $borderThickness;
    public $borderLeftX;
    public $borderHeight;
    public $borderTopY;
    public $borderOuterWidth;
    public $borderBottomY;
    public $borderRightX;

    public $panelWidth;
    public $panelHeight;
    public $panelOffsetX;

    public $panelBodyTopY;
    public $panelBodyHeight;

    public $closeButtonX;
    public $closeButtonY;
    public $closeButtonSize;

    public static function buildDefault()
    {
        $g = new self();

        // constants / tunables
        $g->backgroundWidth  = 90.0;
        $g->backgroundHeight = 60.0;
        $g->rowCount         = 16;

        $g->gap = 0.0;

        // derived geometry
        $g->playerWidth  = $g->backgroundWidth * 0.30;
        $g->playerHeight = $g->backgroundHeight / ($g->rowCount + 1);

        $g->backgroundTopLeftX = -$g->backgroundWidth / 2.0;
        $g->backgroundTopLeftY =  $g->backgroundHeight / 2.0;

        $g->listFrameY = $g->backgroundTopLeftY - ($g->playerHeight / 2.0);

        $g->borderThickness = 0.1;

        $g->borderLeftX      = $g->backgroundTopLeftX - $g->borderThickness;
        $g->borderHeight     = $g->backgroundHeight - $g->playerHeight;
        $g->borderTopY       = $g->listFrameY + $g->borderThickness;
        $g->borderOuterWidth = $g->backgroundWidth + $g->borderThickness * 2.0;
        $g->borderBottomY    = $g->listFrameY - $g->borderHeight;
        $g->borderRightX     = $g->borderLeftX + $g->borderOuterWidth - $g->borderThickness;

        $g->panelWidth = $g->backgroundWidth - $g->playerWidth - $g->gap;
        if ($g->panelWidth < 0) {
            $g->panelWidth = 0;
        }

        $g->panelHeight = $g->backgroundHeight - $g->playerHeight;
        $g->panelOffsetX = $g->playerWidth + $g->gap;

        $g->panelBodyTopY   = -5.0;
        $g->panelBodyHeight = $g->panelHeight - 6.0;

        // closed toggle
        $g->closeButtonX    = 61;
        $g->closeButtonY    = 27.75;
        $g->closeButtonSize = 3.0;

        return $g;
    }
}