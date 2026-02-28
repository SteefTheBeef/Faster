<?php

require_once "helpers/matchlog/utils/MatchlogUtils.php";

////////////////////////////////////////////////////////////////
//
// Date:      02.2026
// Author:    [TnT]BlackCat
// 
////////////////////////////////////////////////////////////////
//
// plugin to show checkpoints times for a whole laps race


registerPlugin('umLiveInfos', 42, 1.0);
function umLiveInfosInit($event) {
    global $playersCheckpointsFromFile;

    $playersCheckpointsFromFile = array();

}
function umLiveInfosPlayerCheckpoint($event, $login) {
    global $_players, $_players_playing;
    umLiveInfosUpdatePlayerCPGapsXml($login, 'show');

    // send check to targetted specs
    $pid = $_players[$login]['PlayerId'];
    foreach ($_players as $speclogin => &$pl) {
        if ($pl['Status'] == 1 && $pl['FinalTime'] <= 0 && ($pl['CurrentTargetId'] == $pid || $_players_playing == 1) &&
            $pl['ML']['ShowML'] && $pl['ML']['Show.live']) {
            if ($pl['ML']['Show.cpgaps'])
                umLiveInfosUpdatePlayerCPGapsXml('' . $speclogin, 'show', $login);
        }
    }
}

function umLiveInfosPlayerSpecChange($event) {
    hideUI();
}

function umLiveInfosBeginRound($event) {
    hideUI();
}

function umLiveInfosBeginRace($event,$GameInfos,$ChallengeInfo,$newcup,$warmup,$fwarmup){
    global $_players, $playersCheckpointsFromFile;
    foreach ($_players as $login => &$pl) {
        $playersCheckpointsFromFile[$login] = MatchlogUtils::getCheckpointsFromFileForPlayer($login);
    }
}
function umLiveInfosEndRace($event) {
    hideUI();
    resetPlayersCheckpoints();
}
function umLiveInfosPlayerConnect($event, $login) {
    global $playersCheckpointsFromFile;
    $playersCheckpointsFromFile[$login] = MatchlogUtils::getCheckpointsFromFileForPlayer($login);
}
//--------------------------------------------------------------
// Function called to handle the manialink drawing
// action can be 'show', 'hide', 'remove'
//--------------------------------------------------------------
function umLiveInfosUpdatePlayerCPGapsXml($login, $action = 'show', $speclogin = '') {
    global $_players, $_GameInfos, $playersCheckpointsFromFile;

    if (!is_string($login))
        $login = '' . $login;

    // if the players disabled manialinks then do nothing
    if (!$_players[$login]['ML']['ShowML'])
        return;

    if ($action == 'remove' || $action == 'hide') {
        manialinksSet($login, 'umLiveInfos.cp', $action);
    }

    // show/refresh
    if (!$_players[$login]['ML']['Show.live'] || !$_players[$login]['ML']['Show.cpgaps'] ||
        ($_players[$login]['IsSpectator'] && $speclogin == '')) {
        // none to show and opened : hide it
        if (manialinksIsOpened($login, 'umLiveInfos.cp'))
            manialinksHide($login, 'umLiveInfos.cp');
        return;
    }

    // show
    $showlogin = $login;
    $msg = '';
    // show spectated login infos
    if ($speclogin != '') {
        $login = $speclogin;
        $msg = '$s$i' . $_players[$login]['NickDraw'] . ':  ';
    }

    if ($_GameInfos['GameMode'] != 3) {
        return;
    }

    if (!isset($playersCheckpointsFromFile[$login]) || count($playersCheckpointsFromFile[$login]) < 2) {
        return;
    }

    $checkpointArray = $playersCheckpointsFromFile[$login];
    $player = $_players[$login];
    $currentCpCount = count($player["Checkpoints"]);
    if ($currentCpCount < 2) {
        return;
    }

    $currentCpArr = $player["Checkpoints"];
    array_shift($currentCpArr);

    $currentCp = array_pop($currentCpArr);
    $fileCp = $checkpointArray[$currentCpCount - 2];
    $checkpointNumberForCurrentLap = count($player["LapCheckpoints"]) - 1;

    if ($_players[$login]['FinalTime'] >= 0) {
        // final time
        $msg .= ' $z$s$w' . MwTimeToString($_players[$login]['FinalTime']) . '  ';
    } else {
        $lap = $_players[$login]['LapNumber'] + 1;
        $msg .= '$z$s$n' . ($lap > 1 ? '$555' . $lap . '..$888' : '$888') . $checkpointNumberForCurrentLap;

        $diff = $currentCp - $fileCp;
        if ($diff > 0) {
            $msg .= '$f00.  $z$s$w$600' . MwDiffTimeToString($diff);
        } else {
            $msg .= '$f00.  $z$s$w$006' . MwDiffTimeToString($diff);
        }
    }

    if ($msg != '') {
        $xml = '<label posn="0 33.7 -60" halign="center" textsize="3" text="' . $msg . '"/>';
        manialinksShow($showlogin, 'umLiveInfos.cp', $xml);
    }
}

function hideUI() {
    global $_players;

    foreach ($_players as $login => &$pl) {
        umLiveInfosUpdatePlayerCPGapsXml($login, 'hide');
    }
}

function resetPlayersCheckpoints() {
    global $playersCheckpointsFromFile;
    $playersCheckpointsFromFile = array();
}