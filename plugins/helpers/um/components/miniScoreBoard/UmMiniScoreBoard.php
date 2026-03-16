<?php


class UmMiniScoreBoard {
    public static function buildPanelXml(UmMiniScoreBoardRenderContext $ctx) {
        $geometry = $ctx->layout->geometryMiniScoreBoard;
        $y = -1;

        if (!isset($ctx->umState->miniScoreBoardIsOpen[$ctx->login]) || !$ctx->umState->miniScoreBoardIsOpen[$ctx->login]) {
            return self::renderToggleIcon($ctx);
        }

        return XmlTag::frame($geometry->mainFrameX, $geometry->mainFrameY, 1,
            "<quad sizen='22 50' posn='0 0' style='BgsPlayerCard' substyle='BgCardSystem'/>"
            . CloseIcon::render(1, -0.5, $ctx->mlAct[UmPanelKeys::ACT_MINI_SCOREBOARD_CLOSE])
            //. XmlTag::label(-5, 29, 15, 2, "\$0f0\$oUM4 Current Score", array(), array('textsize' => 1.0))
            . XmlTag::label(3.1, $y, 20, 2, "\$l[https://umts.vercel.app/um4/semi-final/leaderboard]\$0f0\$oUM4 Semi-Final Score\$l")
            . self::buildTable($ctx, $y)
        );
    }
    static function renderToggleIcon(UmMiniScoreBoardRenderContext $ctx) {
        $geometry = $ctx->layout->geometryMiniScoreBoard;
        $toggleX = $geometry->width - 6.5;
        $toggleY = 13;
        return XmlTag::frame($geometry->mainFrameX, $geometry->mainFrameY, 0,
            XmlTag::label(1.5, -1.25, 5, 3, "\$0f0\$oUM:S")
            . "<quad sizen='6.5 4' posn='{0} {0} 0' style='BgsPlayerCard' substyle='BgPlayerCardSmall' action={$ctx->mlAct[UmPanelKeys::ACT_MINI_SCOREBOARD_OPEN]}/>"
        );
    }
    private static function buildTable(UmMiniScoreBoardRenderContext $ctx, $y) {
        $x = 2;
        $challengeInfo = $ctx->challengeInfo;
        $xml = '';

        $players = $ctx->umState->semiFinalRankings;
        $rowCount = UMPanel::clampInt(count($players), 0, 24);

        $y = $y - 1.7*1.5;
        for ($i = 0; $i < $rowCount; $i++) {
            $player = $players[$i];
            $rank = $i + 1;
            $font = $rank <= 3 ? "\$fc0" : "\$fff";
            $xml .= XmlTag::label($x, $y, 4, 1.5, "Top" . $font . $rank, null);
            $xml .= XmlTag::label($x + 4, $y, 10.5, 1.5, UMPanel::mlStripBold(Player::getName($player)), null, array('autonewline' => 0) );
            $xml .= XmlTag::label($x + 15, $y, 12, 1.5, $font . $player['Score'], null);
            $y = $y - 1.7;
        }

        $playerForLogin = Arrays::find($players, 'Login', $ctx->login, true);
        if ($playerForLogin !== null) {
            $player = $playerForLogin['item'];
            $rank = $playerForLogin['index'] + 1;
            $y = $y - 3;

            $font = $ctx->layout->theme->accentTextColor;

            $xml .= XmlTag::label($x, $y, 12, 1.5, $font . $rank . "/" . count($players), null);
            $xml .= XmlTag::label($x + 4, $y, 12, 1.5, UMPanel::mlStripBold(Player::getName($player)), null);
            $xml .= XmlTag::label($x + 15, $y, 12, 1.5, $font . $player['Score'], null);
        }

        return $xml;
    }
}
