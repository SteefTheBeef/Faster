<?php
// A Player class to handle player-related operations (for now)
class Player {
    public static function getName($player) {
        $playerName = '';
        if (isset($player['NickNameWithColor'])) {
            $playerName = (string)$player['NickNameWithColor'];
        } elseif (isset($player['NickName'])) {
            $playerName = (string)$player['NickName'];
        } elseif (isset($player['Login'])) {
            $playerName = (string)$player['Login'];
        }

        return $playerName;
    }
}