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

registerPlugin(UmPanelKeys::ML_ID_MINI_SCOREBOARD, 44, 1.0);


//--------------------------------------------------------------
// Init : (plugin init)
//--------------------------------------------------------------
function umMiniScoreBoardInit($event) {
    global $_ml_act, $umConfig, $qualiBestRacesConfig, $qualiBestLapsConfig, $umState, $selectPlayerActionIds, $selectSemiFinalRaceActionIds, $layout;

    // get a unique manialink id. Use the same name as for
    // manialinksAddManialink(). It will add its value automatically in <manialink id='xx'>
    manialinksAddId(UmPanelKeys::ML_ID_MINI_SCOREBOARD);
}

function umMiniScoreBoardEndRace($event, $Ranking, $ChallengeInfo, $GameInfos, $continuecup, $warmup, $fwarmup) {
    global $_players, $umState;
    $umState->miniScoreBoardIsOpen = true;
    foreach ($_players as $login => &$pl) {
        umMiniScoreBoardUpdateXml($login, 'show');
    }
}
function umMiniScoreBoardBeginRace($event,$GameInfos,$ChallengeInfo,$newcup,$warmup,$fwarmup){
    global $_players, $umState;
    $umState->miniScoreBoardIsOpen = false;
    foreach ($_players as $login => &$pl) {
        umMiniScoreBoardUpdateXml($login, 'hide');
    }
}

function umMiniScoreBoardPlayerConnect($event, $login) {
    global $umState;
    if ($umState->miniScoreBoardIsOpen) {
        umMiniScoreBoardUpdateXml($login, 'show');
    }

}

function umMiniScoreBoardPlayerStatus2Change($event,$login,$status2){
    global $umState;
    console("umMiniScoreBoardPlayerStatus2Change($login,$status2)");
    if ($umState->miniScoreBoardIsOpen) {
        ml_timesUpdateXmlF($login, 'hide');
    }

}

//--------------------------------------------------------------
// Function called to handle the manialink drawing
// action can be 'show', 'refresh', 'hide', 'remove'
//--------------------------------------------------------------
function umMiniScoreBoardUpdateXml($login, $action = 'show') {
    global $_players, $selectPlayerActionIds, $selectSemiFinalRaceActionIds, $_ml_act, $umConfig, $umState, $layout;

    // if the players disabled manialinks then do nothing

    $ctx = new UmMiniScoreBoardRenderContext($login, $layout, $umConfig, $umState);
    $xml = UmMiniScoreBoard::buildPanelXml($ctx);

    manialinksSet($login, UmPanelKeys::ML_ID_MINI_SCOREBOARD, $action, $xml);
}


?>
