<?php


class UmBoardMini {
    public static function buildPanelXml(UmBoardMiniRenderContext $ctx) {
        $geometry = $ctx->layout->geometryMini;

        if (!$ctx->umState->boardMiniIsOpen[$ctx->login]) {

            $toggleX = $geometry->width - 6.5;
            $toggleY = 13;
            return XmlTag::frame($geometry->mainFrameX, $geometry->mainFrameY, 0,
                XmlTag::label($toggleX + 0.6, $toggleY-1.25, 5, 3, "\$0f0\$oUM:R")
                . "<quad sizen='8 4' posn='{$toggleX} {$toggleY} 0' style='BgsPlayerCard' substyle='BgPlayerCardSmall' action={$ctx->mlAct[UmPanelKeys::ACT_BOARD_MINI_TOGGLE]}/>"
            );
        }

        return XmlTag::frame($geometry->mainFrameX, $geometry->mainFrameY, 0,
            "<quad sizen='22 25' posn='0 35' style='BgsPlayerCard' substyle='BgCardSystem'/>"
            //. XmlTag::label(5, 29, 15, 2, "\$0f0\$oUM4 Records", array(), array('textsize' => 1.0))
            . XmlTag::label(5.5, 34, 15, 2, "\$l[https://umts.vercel.app/um4/quali/leaderboard]\$0f0\$oUM4 Records\$l")
            . XmlTag::quadIcon64(19.25, 34.7, 1.5, 'Circle', $ctx->mlAct[UmPanelKeys::ACT_BOARD_MINI_TOGGLE], array('z' => 0.45))
            . XmlTag::quadIcon64(19.25, 34.7, 1.5, 'Close', $ctx->mlAct[UmPanelKeys::ACT_BOARD_MINI_TOGGLE], array('z' => 0.46))
            . self::buildTable($ctx)
        );
    }

    private static function buildTable(UmBoardMiniRenderContext $ctx) {
        $y = 31.5;
        $x = 1;
        $challengeInfo = $ctx->challengeInfo;
        $xml = '';

        if (isset($ctx->umState->qualificationRankingsPerEnv[$challengeInfo['Environnement']])) {
            $playerCollectionForCurrentEnv = $ctx->umState->qualificationRankingsPerEnv[$challengeInfo['Environnement']];

            $rowCount = UMPanel::clampInt(count($playerCollectionForCurrentEnv), 0, 10);
            for ($i = 0; $i < $rowCount; $i++) {
                $player = $playerCollectionForCurrentEnv[$i];

                $rank = $i + 1;
                $font = $rank <= 3 ? "\$fc0" : "\$fff";
                $xml .= XmlTag::label($x, $y, 4, 1.5, "Top" . $font . $rank, null);
                $xml .= XmlTag::label($x + 4, $y, 12, 1.5, UMPanel::mlStripBold($player['NickNameWithColor']), null);
                $xml .= XmlTag::label($x + 15, $y, 12, 1.5, $font . $player['BestRaceTime'], null);
                $y = $y - 1.7;
            }

            $playerForLogin = Arrays::find($playerCollectionForCurrentEnv, 'Login', $ctx->login, true);
            if ($playerForLogin !== null) {
                $player = $playerForLogin['item'];
                $rank = $playerForLogin['index'] + 1;
                $y = 12.5;

                $font = $ctx->layout->theme->accentTextColor;

                $xml .= XmlTag::label($x, $y, 12, 1.5, $font . $rank . "/" . count($playerCollectionForCurrentEnv), null);
                $xml .= XmlTag::label($x + 4, $y, 12, 1.5, UMPanel::mlStripBold($player['NickNameWithColor']), null);
                $xml .= XmlTag::label($x + 15, $y, 12, 1.5, $font . $player['BestRaceTime'], null);

            }
        }

        return $xml;

    }

    public static function handleAction($login, $action) {
        global $umState;
        $umState = (object)$umState;

        // Panel close/open
        if ($action === UmPanelKeys::ACT_BOARD_MINI_TOGGLE) {
            if (!isset($umState->boardMiniIsOpen[$login])) {
                $umState->boardMiniIsOpen[$login] = true;
            } else {
                $umState->boardMiniIsOpen[$login] = !$umState->boardMiniIsOpen[$login];
            }

            return true;
        }

        return false;
    }
}
