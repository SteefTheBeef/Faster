<?php

class PlayerListPlayoffsPanel {
    static function build(Layout $layout, array $players, $selectedRow, array $actionIds) {
        $padX = 1.0;
        $padY = 1.5;
        $rows = (int)$layout->geometry->rowCount;
        $playerW = $layout->geometry->playerWidth;
        $playerH = $layout->geometry->playerHeight;
        $pointsW = 6.0;
        $pointsRightX = $playerW - $padX;
        $rowSpacing = 0.0;

        $xmlPlayers = '';
        $selectedPlayerIndex = null;

        for ($i = 0; $i < $rows; $i++) {
            $rowY = -$i * ($playerH + $rowSpacing);
            $name = isset($players[$i]) ? $players[$i]['NickNameWithColor'] : '';
            $points = isset($players[$i]) ? $players[$i]['Points'] : '';
            $bg = ($i === (int)$selectedRow) ? $layout->theme->cardSelectedBackgroundColor : $layout->theme->cardBackgroundColor;
            $actionId = isset($actionIds[$i]) ? (int)$actionIds[$i] : 0;

            if ($i === (int)$selectedRow && isset($players[$i])) {
                $selectedPlayerIndex = $i;
            }

            $xmlPlayers .= XmlTag::quad(0, $rowY, $playerW, $playerH, $bg, $actionId);
            $xmlPlayers .= XmlTag::labelCenterLeft($padX, $rowY - $padY, $playerW - 1.2, $playerH, "\$fc0" . ($i + 1));

            $nameLeftX = $padX * 3.0;
            $nameW = ($pointsRightX - $pointsW) - $nameLeftX;

            $xmlPlayers .= XmlTag::labelCenterLeft($nameLeftX, $rowY - $padY, $nameW, $playerH, $name);
            $xmlPlayers .= XmlTag::labelCenterRight($pointsRightX, $rowY - $padY, $pointsW, $playerH, $points);
        }

        return array(
            'xmlPlayers' => $xmlPlayers,
            'selectedPlayerIndex' => $selectedPlayerIndex,
        );
    }
}