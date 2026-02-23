<?php

class LayoutMarkup
{
    public $panelFrameStart;
    public $panelFrameEnd;

    public $playerFrameStart;
    public $playerFrameEnd;

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
        $m->panelFrameStart = "<frame posn='" . ($g->backgroundTopLeftX + $g->panelOffsetX) . " {$g->listFrameY} 0.1'>";
        $m->panelFrameEnd   = "</frame>";

        $m->playerFrameStart = "<frame posn='{$g->backgroundTopLeftX} {$g->listFrameY} 0.1'>";
        $m->playerFrameEnd   = "</frame>";

        $m->frameEnd = "</frame>";

        return $m;
    }
}