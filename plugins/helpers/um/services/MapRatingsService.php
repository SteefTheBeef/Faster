<?php
class MapRatingsService {
    static function getRatingForLogin($login, $ratingsForAllPlayers = array(), $maps = array()) {
        $ratingsForLogin = array();

        if (isset($ratingsForAllPlayers[$login])) {
            $ratingsForLogin = $ratingsForAllPlayers[$login];

            foreach ($maps as $map) {
                $ratingsForLogin[$map->id]['Name'] = $map->nameWithColor;
            }
        } else {
            foreach ($maps as $map) {
                $ratingsForLogin[$map->id] = array(
                    'Name' => $map->nameWithColor,
                    'ChallengeID' => $map->id,
                    'Environment' => $map->environment,
                    'Default' => true
                );

            }
        }

        return $ratingsForLogin;
    }

    static function sortRatingsForLoginByRank(array &$ratingsForLogin) {
        uasort($ratingsForLogin, function ($a, $b) {
            $rankA = isset($a['Rank']) ? (float)$a['Rank'] : 9999;
            $rankB = isset($b['Rank']) ? (float)$b['Rank'] : 9999;

            if ($rankA === $rankB) {
                return 0;
            }

            return ($rankA < $rankB) ? -1 : 1;
        });
    }
}