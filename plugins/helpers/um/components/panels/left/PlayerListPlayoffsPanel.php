<?php

class PlayerListPlayoffsPanel {

    const DISPLAY_POINTS = 'points';
    const DISPLAY_TIME = 'time';

    static function build($ctx, $display = self::DISPLAY_POINTS) {
        $layout = $ctx->layout;
        $umState = $ctx->umState;
        $padX = 1.0;
        $padY = 1.8;
        //$rowCount = UMPanel::clampInt(count($players), 0, $layout->geometry->rowCount);
        $playerW = $layout->geometry->playerWidth;
        $playerH = $layout->geometry->playerHeight;
        $pointsW = 6.0;
        $pointsRightX = $playerW - $padX;
        $rowSpacing = 0.0;

        $actionIds = $ctx->mlAct;
        // console("ACTIONS: " . print_r($ctx->selectPlayerActionIds, true));

        $xmlPlayers = '';
        $selectedPlayerIndex = null;

        $page = $umState->getSelectedPlayerPaginationIndex($ctx->login);
        $players = $umState->selectedPlayerCollection[$ctx->login];
        $playersToShow = UMPanel::playersSliceForPage($players, $page);

        $i = 0;
        console("INDEX: " . $umState->selectedPlayerIndex[$ctx->login]);
        foreach ($playersToShow as $player) {
            $rowY = -$i * ($playerH + $rowSpacing);

            $np = self::normalizePlayerForList($player, $display);

            $name = $np['NickNameWithColor'];
            $pointsOrTime = $np['PointsOrTime'];

            $bg = $i === $umState->selectedPlayerIndex[$ctx->login] ? $layout->theme->cardSelectedBackgroundColor : '';
            $actionId = isset($ctx->selectPlayerActionIds[$i]) ? (int)$ctx->selectPlayerActionIds[$i] : 0;

            //if ($i === (int)$selectedRow && $player !== null) {
            //    $selectedPlayerIndex = $i;
            //}

            //console("ACTION:" . $actionId);

            $xmlPlayers .= XmlTag::quad(0, $rowY, $playerW, $playerH, $bg, $actionId);
            $xmlPlayers .= XmlTag::labelCenterLeft($padX, $rowY - $padY, $playerW - 1.2, $playerH, "\$fc0" . ($page * PLAYERS_PER_PAGE + $i + 1));

            $nameLeftX = $padX * 3.0;
            $nameW = ($pointsRightX - $pointsW) - $nameLeftX;

            $xmlPlayers .= XmlTag::labelCenterLeft($nameLeftX, $rowY - $padY, $nameW, $playerH, $name);
            $xmlPlayers .= XmlTag::labelCenterRight($pointsRightX, $rowY - $padY, $pointsW, $playerH, $pointsOrTime);
            $i++;
        }

        $xmlPlayers .= isset($players) && count($players) > 0 ? PlayerPagination::render($ctx, $players) : XmlTag::label(1, -1, 30, 10, "\$oNo records yet...");

        return array(
            'xmlPlayers' => $xmlPlayers,
            'selectedPlayerIndex' => $selectedPlayerIndex,
        );
    }

    /**
     * Transform arbitrary player payloads into a stable rendering shape.
     *
     * Output shape:
     * - NickNameWithColor: string
     * - PointsOrTime: string
     *
     * @param array<string,mixed>|null $player
     * @param string $display
     * @return array{NickNameWithColor:string, PointsOrTime:string}
     */
    private static function normalizePlayerForList($player, $display) {
        $name = '';
        if (is_array($player)) {
            if (isset($player['NickNameWithColor'])) {
                $name = (string)$player['NickNameWithColor'];
            } elseif (isset($player['NickName'])) {
                $name = (string)$player['NickName'];
            } elseif (isset($player['Name'])) {
                $name = (string)$player['Name'];
            }
        }

        $value = '';
        if (is_array($player)) {
            if ($display === self::DISPLAY_TIME) {
                // Common candidates across different sources; extend as needed.
                if (isset($player['Time']) && $player['Time'] !== '') {
                    $value = (string)$player['Time'];
                } elseif (isset($player['BestRaceTime']) && $player['BestRaceTime'] !== '') {
                    $value = (string)$player['BestRaceTime'];
                }
            } else {
                // DISPLAY_POINTS (default)
                if (isset($player['Points'])) {
                    $value = (string)$player['Points'];
                } elseif (isset($player['Score']) && $player['Score'] !== '') {
                    $value = (string)$player['Score'];
                } elseif (isset($player['Time']) && $player['Time'] !== '') {
                    // Fallback if points aren't available in this dataset
                    $value = (string)$player['Time'];
                }
            }
        }

        return array(
            'NickNameWithColor' => $name,
            'PointsOrTime' => $value,
        );
    }
}