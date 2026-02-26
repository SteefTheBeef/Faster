<?php

class LayoutMarkup
{
    public $rightFrameStart;
    public $rightFrameEnd;

    public $leftFrameStart;
    public $leftFrameEnd;

    public $frameEnd;

    /**
     * @param LayoutGeometry $g
     * @param LayoutTheme    $t currently unused, but kept to allow markup to evolve with theme
     * @return LayoutMarkup
     */
    public static function from(LayoutGeometry $g, LayoutTheme $t)
    {
        $m = new self();

        // Keep string format consistent with the previous output.
        $m->rightFrameStart = "<frame posn='" . ($g->backgroundTopLeftX + $g->panelOffsetX) . " {$g->listFrameY} 0.1'>";
        $m->rightFrameEnd   = "</frame>";

        $m->leftFrameStart = "<frame posn='{$g->backgroundTopLeftX} {$g->listFrameY} 0.1'>";
        $m->leftFrameEnd   = "</frame>";

        $m->frameEnd = "</frame>";

        return $m;
    }
}