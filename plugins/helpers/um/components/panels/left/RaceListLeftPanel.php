<?php

class RaceListLeftPanel {

    const DISPLAY_POINTS = 'points';
    const DISPLAY_TIME = 'time';

    static function render(UmPanelRenderContext $ctx, $display = self::DISPLAY_POINTS) {
        $layout = $ctx->layout;
        $umState = $ctx->umState;
        $padX = 1.0;
        $padY = 1.8;
        //$rowCount = UMPanel::clampInt(count($players), 0, $layout->geometry->rowCount);
        $playerW = $layout->geometry->playerWidth;
        $playerH = $layout->geometry->playerHeight;
        $pointsW = 6.0;
        $pointsRightX = $playerW - 1;
        $rowSpacing = 0.0;

        $actionIds = $ctx->mlAct;

        $races = $umState->semiFinalRaces;

        $xmlPlayers = '';
        $i = 0;

        foreach ($races as $race) {
            $rowY = -$i * ($playerH + $rowSpacing);

            $bg = '';
            if (isset($umState->selectedPlayerIndex[$ctx->login])) {
                $bg = $i === $umState->selectedPlayerIndex[$ctx->login] ? $layout->theme->cardSelectedBackgroundColor : '';
            }

            $actionId = isset($ctx->selectPlayerActionIds[$i]) ? (int)$ctx->selectPlayerActionIds[$i] : 0;

            $xmlPlayers .= XmlTag::quad(0, $rowY, $playerW, $playerH, $bg, $actionId);
            $xmlPlayers .= XmlTag::labelCenterLeft($padX, $rowY - $padY, $playerW - 1.2, $playerH, "\$fc0" . ($i + 1));

            $nameLeftX = $padX * 3.0;
            $nameW = ($pointsRightX - $pointsW) - $nameLeftX;
            $qualiFont = '$060';
            $xmlPlayers .= XmlTag::labelCenterLeft($nameLeftX, $rowY - $padY, $nameW, $playerH, $race['RaceInfo']['Environment']);
            $xmlPlayers .= XmlTag::labelCenterRight($pointsRightX, $rowY - $padY, 15, $playerH, $race['RaceInfo']['Date']);
            $i++;
        }

        return BottomBar::render($ctx) . $xmlPlayers;
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
        $qualiScore = '';
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

                if (isset($player['QualificationScore'])) {
                    $qualiScore = $player['QualificationScore'];
                }
            }
        }



        return array(
            'NickNameWithColor' => $name,
            'PointsOrTime' => $value,
            'QualiScore' => $qualiScore,
        );
    }
}