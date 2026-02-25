<?php

class LayoutTheme
{
    public $borderColor;

    public $panelBackgroundColor;

    public $cardBackgroundColor;
    public $cardSelectedBackgroundColor;

    public $tabBackgroundColor;
    public $tabActiveBackgroundColor;

    public $headerFontStyle;
    public $panelTitleFontStyle;
    public $accentTextColor;

    public static function buildDefault()
    {
        $t = new self();

        $t->borderColor = '010D';

        $t->panelBackgroundColor = '010D';

        $t->cardBackgroundColor = '020D';
        $t->cardSelectedBackgroundColor = '010D';

        $t->tabBackgroundColor = '010D';
        $t->tabActiveBackgroundColor = '060D';

        $t->panelTitleFontStyle = '$fff$o';
        $t->headerFontStyle = '$cf0$o';
        $t->accentTextColor = '$390';

        return $t;
    }
}