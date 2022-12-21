<?php
	//require (dirname(__FILE__)."\..\helpers\matchlog\matchlogLaps.php");


require_once "helpers/matchlog/Matchlog.php";

////////////////////////////////////////////////////////////////
//¤
// File:      FAST 3.2 (First Automatic Server for Trackmania)
// Date:      06.04.2011
// Author:    Gilles Masson
//
////////////////////////////////////////////////////////////////
//
// FAST3.2 Matchlog plugin
//
//
if(!$_is_relay) registerPlugin('matchlog',26,1.0);

global $do_match_log,$matchfilename;


$matchfilename = "fastlog/matchlog.txt";  // real value is in Init
$do_match_log = true;


//--------------------------------------------------------------
// Init :
//--------------------------------------------------------------
function matchlogInit(){
	global $do_match_log,$matchfilename,$htmlmatchfilename,$matchfile,$_Game,$_DedConfig,$_lapspoints_points,$_lapspoints_finishbonus,$_lapspoints_notfinishmultiplier,$_lapspoints_rule;

	$matchfilename = "fastlog/matchlog.".strtolower($_Game).".".$_DedConfig['login'].".txt";
	$htmlmatchfilename = "matchlog.".strtolower($_Game).".".$_DedConfig['login'].".html";

	if(!isset($_lapspoints_rule)) {
		$_lapspoints_rule = 'fet3';
	}

	$_lapspoints_points['fet2'] = array(15,12,11,10,9,8,7,6,6,5,5,4,4,3,3,3,2,2,2,2,1,1,0);
	$_lapspoints_finishbonus['fet2'] = array(100=>5,80=>4,60=>3,40=>2,20=>1); // if >= index% of race, then add indicated bonus, must be in decreasing order !!

  $_lapspoints_points['fet3'] = array(25,22,20,19,18,17,16,15,14,13,12,11,10,10,9,9,8,8,7,7,6,6,5,5,4,4,3,3,2,2,1); // FET6 style points
	$_lapspoints_finishbonus['fet3'] = array(100=>0); // if >= index% of race, then add indicated bonus, must be in decreasing order !!
	$_lapspoints_notfinishmultiplier['fet3'] = 0.5; // coef (round up) for players who did not finish


	// open log file
	if($do_match_log){
		$matchfile = fopen($matchfilename,"ab");
		if($matchfile === false) {
			$do_match_log = false;
		}

	}
}


//--------------------------------------------------------------
// BeginRace :
//--------------------------------------------------------------
function matchlogBeginRace($event,$GameInfos){
	global $_matchlog_Ranking,$do_match_log,$matchfile,$matchfilename;
	$_matchlog_Ranking[0]['Score'] = -1;
	$_matchlog_Ranking[1]['Score'] = -1;

	// re-open log file (sometimes usefull if the file was modified externally after fast init)
	if($do_match_log){
		if($matchfile !== false) {
			@fclose($matchfile);
		}

		$matchfile = fopen($matchfilename,'ab');
	}
}


//------------------------------------------
// BeginRound :
//------------------------------------------
function matchlogBeginRound(){
	global $_Ranking,$_GameInfos;
	if(isMatchlogDisabled()) {
		return;
	}

	Matchlog::create("BEGIN_ROUND", $_GameInfos['GameMode'], null, $_Ranking);
}


//------------------------------------------
// EndRound :
//------------------------------------------
function matchlogEndRound($event,$Ranking,$ChallengeInfo,$GameInfos,$SpecialRestarting){
	if($SpecialRestarting || isMatchlogDisabled() || hasFGameMode("MatchLogEndRound",$event,$Ranking,$ChallengeInfo,$GameInfos, $SpecialRestarting)){
		return;
	}

	Matchlog::create("END_ROUND", $GameInfos["GameMode"], $ChallengeInfo, $Ranking);
}

//------------------------------------------
// RaceFinish
//------------------------------------------
function matchlogEndRace($event,$Ranking,$ChallengeInfo,$GameInfos){
	if(isMatchlogDisabled() || hasFGameMode("MatchLogEndRace",$event,$Ranking,$ChallengeInfo,$GameInfos, null)) {
		return;
	}

	Matchlog::create("END_RACE", $GameInfos["GameMode"], $ChallengeInfo, $Ranking);
}

// -----------------------------------
function matchlogEndResult($event){
	global $do_match_log,$_matchlog_copy,$_matchlog_url,$matchfilename,$htmlmatchfilename,$_WarmUp,$_FWarmUp;
	if (isMatchlogDisabled()) {
		return;
	}

	// copy matchlog
	if(isset($_matchlog_copy)){

		// make html matchlog
		$datas = '<html><head><meta http-equiv="Content-Type" content="text/html; charset=UTF-8"><title>TM Match</title></head><body><pre>';
		$datas .= htmlspecialchars(file_get_contents($matchfilename),ENT_QUOTES,'UTF-8');
		$datas .= '</pre></body></html>';
		$nb = file_put_contents('fastlog/'.$htmlmatchfilename,$datas);

		if($nb > 100){
			// copy html matchlog
			console("Copy fastlog/$htmlmatchfilename ($nb/".strlen($datas).")...");

			if(isset($_matchlog_url)) {
				$addcall = array(null,'ChatSendServerMessage',
					localeText(null,'server_message').'$l['.$_matchlog_url.$htmlmatchfilename.']matchlog copied.');
			} else {
				$addcall = null;
			}

			file_copy('fastlog/'.$htmlmatchfilename,$_matchlog_copy.$htmlmatchfilename,$addcall);
		}
	}
}


//------------------------------------------
// write in match log with time
//------------------------------------------
function matchlog($text){
	global $matchfile,$do_match_log;
	if($do_match_log){
		fwrite($matchfile,"[".date("Y-m-d, H:i:s")."] $text\n");
		fflush($matchfile);
	}
}

function isMatchlogDisabled() {
	global $do_match_log,$_WarmUp,$_FWarmUp;
	return !$do_match_log || $_WarmUp || $_FWarmUp > 0;
}

/**
 * Not sure what this function does.
 *
 * @param $fGameModeIndex
 * @param $event
 * @param $Ranking
 * @param $ChallengeInfo
 * @param $GameInfos
 * @param $SpecialRestarting
 * @return boolean
 */
function hasFGameMode($fGameModeIndex, $event, $Ranking, $ChallengeInfo, $GameInfos, $SpecialRestarting) {
	global $_FGameModes, $_FGameMode;

	if(isset($_FGameModes[$_FGameMode][$fGameModeIndex]) && $_FGameModes[$_FGameMode][$fGameModeIndex] != ''
		&& function_exists($_FGameModes[$_FGameMode][$fGameModeIndex])){
		// call FGameMode matchlog callback if exists
		if (isset($SpecialRestarting)) {
			call_user_func($_FGameModes[$_FGameMode][$fGameModeIndex],$event,$Ranking,$ChallengeInfo,$GameInfos,$SpecialRestarting);
		} else {
			call_user_func($_FGameModes[$_FGameMode][$fGameModeIndex],$event,$Ranking,$ChallengeInfo,$GameInfos);
		}

		return true;
	}

	return false;
}

?>
