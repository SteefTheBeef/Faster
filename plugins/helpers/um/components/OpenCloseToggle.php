<?php

class OpenCloseToggle {
    public static function render($ctx) {
        $isClosed = (int)$ctx->umState->boardIsOpen[$ctx->login] == false;

        if ($isClosed) {
            return self::buildClosedToggleXml($ctx->layout, $ctx->mlAct);
        }

        $panelW = $ctx->layout->geometry->panelWidth;

        $closeSize = 2.2;
        $closeMarginR = 0.25;
        $closeY = -0.5;
        $closeX = $panelW - $closeMarginR - $closeSize;
        if ($closeX < 0) $closeX = 0;

        $closeActId = isset($ctx->mlAct[UmPanelKeys::ACT_PANEL_CLOSE]) ? (int)$ctx->mlAct[UmPanelKeys::ACT_PANEL_CLOSE] : 0;

        return
            XmlTag::quadIcon64($closeX, $closeY, $closeSize, 'Circle', $closeActId, array('z' => 0.45))
            . XmlTag::quadIcon64($closeX, $closeY, $closeSize, 'Close', $closeActId, array('z' => 0.46));
    }

    private static function buildClosedToggleXml(Layout $layout, array $mlAct) {
        $x = $layout->geometry->closeButtonX;
        $y = $layout->geometry->closeButtonY;
        $s = $layout->geometry->closeButtonSize;

        $actId = isset($mlAct[UmPanelKeys::ACT_PANEL_OPEN]) ? (int)$mlAct[UmPanelKeys::ACT_PANEL_OPEN] : 0;
        $font = $layout->theme->accentTextColor;
        $label = XmlTag::label(0.75, -1.25, $s, $s, "\$0f0\$oUM");
        return XmlTag::frame($x - 1.25, $y - 0.5, 5, $label . "<quad sizen='6 4' posn='0 0 0' style='BgsPlayerCard' substyle='BgPlayerCardSmall' action={$actId}/>");
    }
}

