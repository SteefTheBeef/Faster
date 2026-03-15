<?php
////////////////////////////////////////////////////////////////
//
// Date:      02.2026
// Author:    [TnT]BlackCat
// 
////////////////////////////////////////////////////////////////

// Main component
require_once "helpers/um/components/mini/UmBoardMini.php";
require_once "helpers/um/components/mini/UmBoardMiniRenderContext.php";
require_once "helpers/um/domain/UmPanelKeys.php";

//registerPlugin(UmPanelKeys::ML_ID_BOARD_MINI, 45, 1.0);


//--------------------------------------------------------------
// Init : (plugin init)
//--------------------------------------------------------------
function umBoardMiniInit($event) {
    global $_ml_act, $umConfig, $umState, $layout;
    manialinksAddId(UmPanelKeys::ML_ID_BOARD_MINI);

    if (!($layout instanceof Layout)) {
        $layout = Layout::build();
    }

    if (!($umConfig instanceof UMConfig)) {
        $umConfig = new UMConfig();
    }

    if (!($umState instanceof UmState)) {
        $umState = new UmState($umConfig->um4QualiBestRace, $umConfig->um4QualiBestLap);
    }

    manialinksAddAction(UmPanelKeys::ACT_BOARD_MINI_TOGGLE);
}

//--------------------------------------------------------------
// PlayerConnect : (event from server callback)
//--------------------------------------------------------------
function umBoardMiniPlayerConnect($event, $login) {
    umBoardMiniUpdateXml($login, 'show');
}
function umBoardMiniPlayerDisconnect($event,$login){
    global $umState;
    $umState = (object)$umState;
    $umState->playerDisconnect($login);
}

function umBoardMiniEndRace($event, $Ranking, $ChallengeInfo, $GameInfos, $continuecup, $warmup, $fwarmup) {
    global $_players;

    foreach ($_players as $login => &$pl) {
        umBoardMiniUpdateXml($login, 'show');
    }
}
function umBoardMiniBeginRace($event,$GameInfos,$ChallengeInfo,$newcup,$warmup,$fwarmup){
    global $_players;
    console("umBoardMiniBeginRace");
    foreach ($_players as $login => &$pl) {
        umBoardMiniUpdateXml($login, 'show');
    }
}
//--------------------------------------------------------------
// PlayerShowML : (event from manialink plugin when the player set it on/off)
//--------------------------------------------------------------
function umBoardMiniPlayerShowML($event, $login, $ShowML) {
    if ($ShowML > 0)
        umBoardMiniUpdateXml($login, 'show');
}

//--------------------------------------------------------------
// PlayerManialinkPageAnswer : (event from server callback)
//--------------------------------------------------------------
function umBoardMiniPlayerManialinkPageAnswer($event, $login, $answer, $action) {
    UmBoardMini::handleAction($login, $action);
    umBoardMiniUpdateXml($login, 'show');
}

//--------------------------------------------------------------
// Function called to handle the manialink drawing
// action can be 'show', 'refresh', 'hide', 'remove'
//--------------------------------------------------------------
function umBoardMiniUpdateXml($login, $action = 'show') {
    global $_players, $selectPlayerActionIds, $_ml_act, $umConfig, $umState, $layout;

    // if the players disabled manialinks then do nothing
    if (!isset($_players[$login]['ML']['ShowML']) || $_players[$login]['ML']['ShowML'] <= 0)
        return;

    $ctx = new UmBoardMiniRenderContext($login, $layout, $_ml_act, $umConfig, $umState);
    $xml = UmBoardMini::buildPanelXml($ctx);

    manialinksSet($login, UmPanelKeys::ML_ID_BOARD_MINI, $action, $xml);
}

?>
