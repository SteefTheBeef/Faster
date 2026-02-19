<?php
////////////////////////////////////////////////////////////////
//¤
// File:      FAST 3.2 (First Automatic Server for Trackmania)
// Web:       
// Date:      25.08.2011
// Author:    [TnT]BlackCat
// 
////////////////////////////////////////////////////////////////
// needed plugins: manialinks,ml_menus
//
// plugin to show checkpoints times for a whole laps race

require_once "helpers/matchlog/utils/MatchlogUtils.php";
registerPlugin('um_liveinfos', 42, 1.0);

function um_liveinfosPlayerCheckpoint($event, $login) {
    global $_mldebug, $_players, $_players_playing;
    um_liveinfosUpdatePlayerCPGapsXml($login, 'show');

    // send check to targetted specs
    $pid = $_players[$login]['PlayerId'];
    foreach ($_players as $speclogin => &$pl) {
        if ($pl['Status'] == 1 && $pl['FinalTime'] <= 0 && ($pl['CurrentTargetId'] == $pid || $_players_playing == 1) &&
            $pl['ML']['ShowML'] && $pl['ML']['Show.live']) {
            if ($pl['ML']['Show.cpgaps'])
                console("CP CHECK!!");
                um_liveinfosUpdatePlayerCPGapsXml('' . $speclogin, 'show', $login);
        }
    }
}

function um_liveinfosPlayerSpecChange($event) {
    hideUI();
}

function um_liveinfosBeginRound($event) {
    hideUI();
}

function um_liveinfosEndRace($event) {
    hideUI();
}

//--------------------------------------------------------------
// Function called to handle the manialink drawing
// action can be 'show', 'hide', 'remove'
//--------------------------------------------------------------
function um_liveinfosUpdatePlayerCPGapsXml($login, $action = 'show', $speclogin = '') {
    global $_players, $_GameInfos;

    if (!is_string($login))
        $login = '' . $login;

    // if the players disabled manialinks then do nothing
    if (!$_players[$login]['ML']['ShowML'])
        return;

    if ($action == 'remove' || $action == 'hide') {
        manialinksSet($login, 'um_liveinfos.cp', $action);
    }

    // show/refresh
    if (!$_players[$login]['ML']['Show.live'] || !$_players[$login]['ML']['Show.cpgaps'] ||
        ($_players[$login]['IsSpectator'] && $speclogin == '')) {
        // none to show and opened : hide it
        if (manialinksIsOpened($login, 'um_liveinfos.cp'))
            manialinksHide($login, 'um_liveinfos.cp');
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

    if ($_GameInfos['GameMode'] != LAPS) {
        return;
    }

    $checkpointArray = MatchlogUtils::getCheckpointsFromFileForPlayer($login);
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
        manialinksShow($showlogin, 'um_liveinfos.cp', $xml);
    }
}

function hideUI() {
    global $_players;

    foreach ($_players as $login => &$pl) {
        um_liveinfosUpdatePlayerCPGapsXml($login, 'hide');
    }
}