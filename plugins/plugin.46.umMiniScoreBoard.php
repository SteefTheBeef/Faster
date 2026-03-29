<?php
////////////////////////////////////////////////////////////////
//
// Date:      03.2026
// Author:    [TnT]BlackCat
// 
////////////////////////////////////////////////////////////////


// Main component
require_once "helpers/um/components/miniScoreBoard/UmMiniScoreBoard.php";
require_once "helpers/um/components/miniScoreBoard/UmMiniScoreBoardRenderContext.php";

registerPlugin(UmPanelKeys::ML_ID_MINI_SCOREBOARD, 46, 1.0);


//--------------------------------------------------------------
// Init : (plugin init)
//--------------------------------------------------------------
function umMiniScoreBoardInit($event) {
    global $_ml_act, $umConfig, $qualiBestRacesConfig, $qualiBestLapsConfig, $umState, $selectPlayerActionIds, $selectSemiFinalRaceActionIds, $layout;

    // get a unique manialink id. Use the same name as for
    // manialinksAddManialink(). It will add its value automatically in <manialink id='xx'>
    manialinksAddId(UmPanelKeys::ML_ID_MINI_SCOREBOARD);
    manialinksAddAction(UmPanelKeys::ACT_MINI_SCOREBOARD_OPEN);
    manialinksAddAction(UmPanelKeys::ACT_MINI_SCOREBOARD_CLOSE);
}

function umMiniScoreBoardEndRace($event, $Ranking, $ChallengeInfo, $GameInfos, $continuecup, $warmup, $fwarmup) {
    global $_players, $umState;
    $umState->raceIsEnding = true;
    computeRankings();

    // show mini scoreboard for all players and spectators
    foreach ($_players as $login => &$pl) {
        $umState->miniScoreBoardIsOpen[$login] = true;
        umMiniScoreBoardUpdateXml($login, 'show');

        // disable ml_times.mod to hide dedimania left panel on end race.
        $pl['ML']['ml_times.mod'] = false;
        ml_timesUpdateXmlF($login, 'hide');
    }
}

function umMiniScoreBoardBeginRace($event, $GameInfos, $ChallengeInfo, $newcup, $warmup, $fwarmup) {
    global $_players, $umState, $_ml_times_default_mod;
    $umState->raceIsEnding = false;

    foreach ($_players as $login => &$pl) {
        $pl['ML']['ml_times.mod'] = $_ml_times_default_mod;
        // Hide for all active player, but not spectators.
        if ($pl['IsSpectator'] != 1) {
            $umState->miniScoreBoardIsOpen[$login] = false;
        }

        umMiniScoreBoardUpdateXml($login, 'show');
    }
}

function umMiniScoreBoardPlayerConnect($event, $login) {
    global $umState, $_players;

    // if race has ended show mini scoreboard when player connects
    if ($umState->raceIsEnding) {
        $umState->miniScoreBoardIsOpen[$login] = true;
    }

    umMiniScoreBoardUpdateXml($login, 'show');
}

function umMiniScoreBoardPlayerStatus2Change($event, $login, $status2) {
    global $umState, $_players;

    if ($umState->raceIsEnding) {
        ml_timesUpdateXmlF($login, 'hide');
    }
}

function umMiniScoreBoardPlayerManialinkPageAnswer($event, $login, $answer, $action) {
    global $umState;

    if ($umState->raceIsEnding) {
        $umState->miniScoreBoardIsOpen[$login] = true;
    }

    if ($action == 'ml_main.cross') {
        ml_timesUpdateXmlF($login, 'hide');
    }

    if ($action == UmPanelKeys::ACT_MINI_SCOREBOARD_CLOSE) {
        $umState->miniScoreBoardIsOpen[$login] = false;
    }

    if ($action == UmPanelKeys::ACT_MINI_SCOREBOARD_OPEN) {
        $umState->miniScoreBoardIsOpen[$login] = true;
    }

    umMiniScoreBoardUpdateXml($login, 'show');
}

function umMiniScoreBoardPlayerShowML($event, $login, $ShowML) {
    if ($ShowML > 0)
        umMiniScoreBoardUpdateXml($login, 'show');
}

//--------------------------------------------------------------
// Function called to handle the manialink drawing
// action can be 'show', 'refresh', 'hide', 'remove'
//--------------------------------------------------------------
function umMiniScoreBoardUpdateXml($login, $action = 'show') {
    global $_players, $selectPlayerActionIds, $selectSemiFinalRaceActionIds, $_ml_act, $umConfig, $umState, $layout;

    // if the players disabled manialinks then do nothing

    $ctx = new UmMiniScoreBoardRenderContext($login, $layout, $umConfig, $umState, $_ml_act);
    $xml = UmMiniScoreBoard::buildPanelXml($ctx);

    manialinksSet($login, UmPanelKeys::ML_ID_MINI_SCOREBOARD, $action, $xml);
}


?>
