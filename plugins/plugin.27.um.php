<?php
////////////////////////////////////////////////////////////////
//¤
// File:      FAST 3.2 (First Automatic Server for Trackmania)
// Date:      07.12.2011
// Author:    Gilles Masson
//
////////////////////////////////////////////////////////////////
// uncomment the next line to activate the um plugin and see all events in log !

registerPlugin('um',27);



////////////////////////////////////////////////////////////////
//
// Here is an example of all possible events, with some indication about their origin
// To active the plugin and have most events indicated in log and console, just
// uncomment the registerPlugin() call.
//
// Note that for each event, the plugins functions will be called in the order defined
// by the plugins priority.
// Advanced usage : for some specials use it is possible that a plugin need to have
// it's function called later, ie after the plugins which have bigger priority number.
// It is possible by adding _Reverse ou _Post at the end of the function name. For example,
// for Init event, xxxInit() are called first in priority order, then xxxInit_Reverse() are
// called in reversed priority order, then finally xxxInit_Post() are called in priority order.
//


////////////////////////////////////////////////////////////////
//
// Fast now handle ChatEnableManualRouting. See in fast_general.php the comments of
// the function sendPlayerChat() to see how to handle it in plugins !


////////////////////////////////////////////////////////////////
// general variables/arrays description :
////////////////////////////////////////////////////////////////

// $_currentTime : current time (ms)
// $everysecond : seconds (used for Everysecond callback)
// $every5seconds : seconds, 5s rounded (used for Every5seconds callback)
// $everyminute : minutes (used for Everyminute callback)

// $_Version : GetVersion values
// $_SystemInfo : GetSystemInfo values
// $_ServerInfos : GetDetailedPlayerInfo values for serverlogin
// $_ServerPackMask : GetServerPackMask value
// $_ServerId : playerid of server
// $_methods_list : list of known methods (from system.listMethods)
// $_use_flowcontrol : true/false, is the server using manualflowcontrol ?
// $_is_relay : true/false, is the server a relay ?
// $_master : if relay, GetMainServerPlayerInfo values
// $_relays : list of connected relays
// $_multidest_logins : true if server support multi comma separated logins in xxxToLogin() methods

// $_StatusCode : current game status code value
// $_Status : current game status, array of kind ('Code'=>4,'Name'=>'Running - Play')
// $_old_Status : old value of $_Status
// $_ChallengeList : challenges list
// $_CurrentChallengeIndex : current challenge index
// $_NextChallengeIndex : next challenge index
// $_ChallengeInfo : current challenge infos
// $_NextChallengeInfo : next challenge infos (supposed until EndPodium)
// $_PrevChallengeInfo : previous challenge infos
// $_GameInfos : current game infos
// $_NextGameInfos : next game infos
// $_ServerOptions : current server options
// $_PlayerList : players list (use $_players instead)
// $_Ranking : rankings list (use $_players instead)
// $_PlayerInfo : players detailed infos (use $_players instead)
// $_NetworkStats : server/players NetworkStats
// $_RoundCustomPoints : server RoundCustomPoints (see also plugin.04.roundspoints.php)
// $_CallVoteRatios : votes ratios
// $_ForcedMods : ForcedMods
// $_ForcedMusic : ForcedMusic
// $_ServerCoppers : ServerCoppers
// $_LadderServerLimits: LadderServerLimits
// $_EndMatchCondition : EndMatchCondition state in Cup ('Finished','ChangeMap','Playing')
// $_GuestList : guest players list
// $_IgnoreList : ignored players list
// $_BanList : banned players list
// $_BlackList : blacklisted players list

// $_IsFalseStart : is it currently a false start ?



////////////////////////////////////////////////////////////////
// players plugin variables/arrays (see also the beginning of plugin.01.players.php for them) :

// State info tables :
// $_players : main players table
//  note: disconnected players have $_players['Active']===false (old disconnected players are in: $_players_old)
// $_players_positions : player positions table in current round
// $_players_rounds_scores : previous rounds scores
// $_teams : scores infos for teams.

// State infos :
// $_players_round_restarting : the round is currently in special restart (falsestart/error)
// $_players_round_restarting_wu : state of warmup before the special restart
// $_players_prev_map_uid : uid of previous map (not changed by special restart)

// $_players_round_time : msec at start of round (make diff with $_currentTime to know round duration)
// $_players_round_current : num of the current round (1 for 1st, 0 before 1st BeginRound)
// $_players_roundplayed_current : num of really played current round
// $_players_round_finished : if >0 then current round was really finished (count all finishes)
// $_players_actives : number of players (including spectators)
// $_players_spec : number of spectators (pure spectators, not tmp specs who gave up)
// $_players_playing : number of players actually playing
// $_players_finished : number of player who have finished the current round (ie who have finished at least one time)
// $_players_giveup : number of players who 'del' (after at least 1 checkpoint)
// $_players_giveup2 : number of players who 'del'
// $_players_round_checkpoints
// $_players_firstmap : true only during 1st map when the script is started.
// $_players_firstround : true only during 1st map and round when the script is started.
// $_players_missings : number of missing PlayerFinish callbacks (should never happen, btw it occasionally happens...)
// $_players_round_checkpoints : number of checkpoints events (of all players) in round.

// $_NumberOfChecks : number of checkpoints by lap.
// $_LastCheckNum : last race checkpoint index.

// $_always_use_FWarmUp : if true the try to always use FWarmUp instead of classic WarmUp
// $_FWarmUpDuration : FWarmUp duration (game config for next)
// $_FWarmUp : FWarmUp current (duration)
// $_NextFWarmUp : FWarmUp set at EndRace for next race (duration)
// $_FWarmUpState : FWarmUp current state (0 to duration)



// Helps for debugging, you can set those values in fast.php :

// general debug level
//$_debug = 1;

// general debug level for manialinks
//$_mldebug = 0;

// debug level for dedicated callbacks/events
//$_dedebug = 0;

// debug level for memtests
//$_memdebug = 0;

// specific debug info for specified dedicated methods calls, example:
// $_cdebug['ForceSpectator'] = 0;
//    means that addCall(,'ForceSpectator',) will show a calling line info if debug level > 0

// specific debug info for specified Fast events calls, example:
// $_edebug['PlayerCheckpoint'] = 0;
//    means that addEvent(,'PlayerCheckpoint',) will show a calling line info if debug level > 0

// specific debug level overiding the general one during specified plugin events, examples:
// $_pdebug['fteamrelay']['debug'] = 3; // all 'fteamrelay' events will have $_debug = 3
// $_pdebug['ml_main']['mldebug'] = 4; // all 'ml_main'events will have $_mldebug = 4
// $_pdebug['ALL']['BeginRound']['debug'] = 6; // BeginRound() will have $_debug = 6 for all plugins
// $_pdebug['fteamrelay']['PlayerConnect']['debug'] = 5; // except fteamrelayPlayerConnect() will have $_debug = 5
// $_pdebug['fteamrelay']['BeginRound']['debug'] = 1; // except fteamrelayBeginRound() will have $_debug = 1
// $_pdebug['players']['PlayerCheckpoint']['debug'] = 2; // except fteamrelayBeginRound() will have $_debug = 1
// $_pdebug['players']['PlayerFinish']['debug'] = 2; // except fteamrelayBeginRound() will have $_debug = 1

function sendLeaderboardRankChat($login) {
	global $_ChallengeInfo;
	$leaderboard = fopen("data/um/leaderboard.txt", "r") or die("Unable to open file!");

	while(!feof($leaderboard)) {
		$line = explode(",", fgets($leaderboard));
		if ($line[0] === $login) {
			console(print_r($line));
			$msg = '$sWelcome back to United Masters, '.$line[1].'$z$s$fff'."\n".'Your current leaderboard rank is $0f0'.$line[2].'$fff with $0f0'.trim($line[3]).' $fffpoints';
			addCall($login,'ChatSendServerMessage', $msg);
			fclose($leaderboard);
			break;
		}
	}
}

function sendEnviRankChat($login) {
	global $_ChallengeInfo;
	$currentChallengeFile = fopen("data/um/".$_ChallengeInfo["Environnement"].".txt", "r") or die("Unable to open file!");
	while(!feof($currentChallengeFile)) {
		$line = explode(",", fgets($currentChallengeFile));
		if ($line[0] === $login) {
			$msg = '$s$fffYour current rank on '.$_ChallengeInfo["Environnement"].' is $0f0'.$line[2].' $fffwith a race time of: $0f0'.trim($line[3]);
			addCall($login,'ChatSendServerMessage', $msg);
			fclose($currentChallengeFile);
			return;
		}
	}
}

////////////////////////////////////////////////////////////////
// Example for all events handled by Fast
////////////////////////////////////////////////////////////////

// Init($event): used for init of plugins, called after includes and config, but before StartToServe
// (Fast event)
function umInit($event){
	global $_debug;
	if($_debug>0) console("um.Event[$event]");
}

// StartToServe($event): called at beginning, before the main loop, but after plugins Init
// (Fast event)
function umStartToServe($event){
	global $_debug;
	if($_debug>0) console("um.Event[$event]");
}

// ServerStart($event): called at beginning when all server infos are read
// (from TrackMania.ServerStart callback, or Fast simulated)
function umServerStart($event){
	global $_debug;
	if($_debug>0) console("um.Event[$event]");
}

// BeginRace($event,$GameInfos,$newcup,$warmup): start of race
// (from TrackMania.BeginRace callback, or Fast simulated)
function umBeginRace($event,$GameInfos,$ChallengeInfo,$newcup,$warmup,$fwarmup){
	global $_debug, $_players;
	if($_debug>0) console("um.Event[$event]($newcup,$warmup,$fwarmup)");
	foreach($_players as $login => &$player) {
		sendLeaderboardRankChat($login);
		sendEnviRankChat($login);
	}

}

// StartRace($event,$tm_db_n,$chalinfo,$ChallengeInfo): $chalinfo is the array returned by the database server
// (Fast database plugin event)
function umStartRace($event,$tm_db_n,$chalinfo,$ChallengeInfo){
	global $_debug;
	if($_debug>0) console("um.Event[$event]");
}

// BeforePlay($event,$delay,$time)
// (from TrackMania.ManualFlowControlTransition callback)
// need ManualFlowControl to be activated for script !!! ie only if global $_use_flowcontrol == true
//  Transition 'Synchro -> Play' : before BeginRound and StatusChanged 3->4, seconds after StatusChanged 2->3 or 4->3 and EndRound
// $delay is milliseconds since callback was received. Can delay transition using: delayTransition($delay);
// $time is the script time at TrackMania.ManualFlowControlTransition callback
// The transition method is called a first time with delay 0, then eventually other times
// and called a last time with delay -1 just before to proceed transition (where delayTransition($delay) will have no effect)
// time is the original time when transition was received
function umBeforePlay($event,$delay,$time){
	global $_debug;
	if($_debug>0) console("um.Event[$event]($delay,$time)");
}

// BeginRound($event)
// (from TrackMania.BeginRound callback, or Fast simulated)
function umBeginRound($event){
	global $_debug;
	if($_debug>0) console("um.Event[$event]");
}

// PlayerConnect($event,$login,$pinfo,$pdetailedinfo,$pranking): player has connected
// (from TrackMania.PlayerConnect callback, or Fast simulated)
function umPlayerConnect($event,$login,$pinfo,$pdetailedinfo,$pranking){
	global $_debug, $_ChallengeInfo;

	if($_debug>0) console("um.Event[$event]('$login')");
		console(print_r($pinfo));


	$leaderboard = fopen("data/um/leaderboard.txt", "r") or die("Unable to open file!");

	while(!feof($leaderboard)) {
		$line = explode(",", fgets($leaderboard));
		if ($line[0] === $login) {
			console(print_r($line));
			$msg = '$sWelcome back to United Masters, '.$line[1].'$z$s$fff';
			addCall($login,'ChatSendServerMessage', $msg);
			fclose($leaderboard);
			return;
		}
	}

	addCall($login,'ChatSendServerMessage', '$sWelcome to United Masters, '.$pinfo["NickName"]);
}

// PlayerMenuBuild($event,$login): build player menu
function umPlayerMenuBuild($event,$login){
	global $_debug;
	if($_debug>0) console("um.Event[$event]('$login')");
}

// PlayerUpdate($event,$login,$player): some change in player list or ranking data ($player is an array with changes)
// (Fast event)
function umPlayerUpdate($event,$login,$player){
	global $_debug;
	if($_debug>0){
		$msg = "";
		$sep = "";
		foreach($player as $key => $val){
			$msg .= $sep.$key."=".$val;
			$sep = ",";
		}
		console("um.Event[$event]('$login',$msg)");
	}
}



// Everyminute($event,$minutes,$is2min,$is5min):
//        called once every minute, after all other events (before Every5seconds)
//       for other timing, use test   if(($minutes%XX)==0)  where XX if the every wanted minutes
// (Fast event)
function umEveryminute($event,$minutes,$is2min,$is5min){
	global $_debug;
	if($_debug>0){
		//console("um.Event[$event]($minutes,$is2min,$is5min)");
		if(($minutes%8)==0) console("umEveryminute - every 8 minutes");
	}
}

// Every5seconds($event,$seconds): called once every 5 seconds, after all other events (before Everysecond)
// (Fast event)
function umEvery5seconds($event,$seconds){
	global $_debug;
	//if($_debug>2) console("um.Event[$event]($seconds)");
}

// Everysecond($event,$seconds): called once every second, after all other events (before Everytime)
// (Fast event)
function umEverysecond($event,$seconds){
	global $_debug;
	//if($_debug>3) console("um.Event[$event]($seconds)");
}

// Everytime($event): called at every mainloop, after all other events
// (Fast event)
function umEverytime($event){
	global $_debug;
	//if($_debug>4) console("um.Event[$event]");
}

// PlayerChat($event,$login,$message): the player wrote a text in chat
// (from TrackMania.PlayerChat callback, or Fast simulated)
function umPlayerChat($event,$login,$message,$iscommand){
	global $_debug;
	if($_debug>3) console("um.Event[$event]('$login','$message',$iscommand)");
}

// PlayerStart($event,$login,$starttime): send this event when player start (in TA)
// (from TrackMania.PlayerFinish(login,0))
function umPlayerStart($event,$login,$starttime){
	global $_debug;
	if($_debug>0) console("um.Event[$event]($login,$starttime)");
}

// PlayerCheckpoint($event,$login,$time,$lapnum,$checkpt,$hiddenabort): player passed a checkpoint
// (from TrackMania.PlayerCheckpoint callback)
// $hiddenabort is true in case of TA with $checkpt==0 after a respawn+del,
// which don't make a PlayerFinish(login,0). Exists since Fast 3.2.3g
function umPlayerCheckpoint($event,$login,$time,$lapnum,$checkpt,$hiddenabort=false){
	global $_debug;
	if($_debug>0) console("um.Event[$event]('$login',$time,$lapnum,$checkpt,$hiddenabort)");
}

// PlayerLap($event,$login,$time,$lapnum,$checkpt): player lap time (in Laps mode only)
// (Fast players plugin event)
function umPlayerLap($event,$login,$time,$lapnum,$checkpt,$checkpts){
	global $_debug;
	if($_debug>0) console("um.Event[$event]('$login',$time,$lapnum,$checkpt)");
}

// PlayerMenuAction($event,$login,$action,$state): player have triggered a menu entry
// (Fast ml_menus plugin event)
function umPlayerMenuAction($event,$login,$action,$state){
	global $_debug;
	if($_debug>0) console("um.Event[$event]('$login',$action,$state)");
}


// PlayerPositionChange($event,$login,$changes): player position change (rounds, team and laps)
//  ($changes: bit 0=position change, bit 1=check change, bit 2=previous player or time change)
//  (bit 3=next player or time change, bit 4=first player or time change)
//  (bit 5=prev2 player or time change, bit 6=next2 player or time change)
//  (if $login===true then there where some changes in players positions or times)
// (Fast players plugin event)
function umPlayerPositionChange($event,$login,$changes){
	global $_debug;
	if($_debug>0) console("um.Event[$event]('$login',$changes)");
	// $changes value :
	//   1=position changed  (in TA: best rank changed)
	//   2=checkpoint changed
	//   4=diff with previous player changed  (not in TA)
	//   8=diff with next player changed  (not in TA)
	//  16=diff with first player changed  (in TA: best diff)
	//  32=diff with previous2 player changed  (not in TA)
	//  64=diff with next2 player changed  (not in TA)
}

// PlayerBest($event,$login,$time,$ChallengeInfo,$checkpts): player made his best time (or lap in Laps mode on dedicated)
// (Fast players plugin event)
function umPlayerBest($event,$login,$time,$ChallengeInfo,$GameInfos,$checkpts){
	global $_debug;
	if($_debug>0) console("um.Event[$event]('$login',$time)");
}

// PlayerFinish($event,$login,$time,$checkpts): player has finished the round/run/lap
// (from TrackMania.PlayerFinish callback, $checkpts added by players plugin)
function umPlayerFinish($event,$login,$time,$checkpts){
	global $_debug;
	if($_debug>0) console("um.Event[$event]('$login',$time)");
}

// PlayerTeamChange($event,$login,$teamid): player team change
// (Fast players plugin event)
function umPlayerTeamChange($event,$login,$teamid){
	global $_debug;
	if($_debug>0) console("um.Event[$event]('$login',$teamid)");
}

// PlayerStatusChange($event,$login,$status,$oldstatus): player status change
// $status based on strict game hud: 0=playing, 1=spec, 2=race finished
// (Fast players plugin event)
function umPlayerStatusChange($event,$login,$status,$oldstatus){
	global $_debug;
	if($_debug>0) console("um.Event[$event]('$login',$status)");
}

// PlayerStatus2Change($event,$login,$status2,$oldstatus2): player status change
// round logical $status2: 0=playing, 1=spec, 2=race finished
// (Fast players plugin event)
function umPlayerStatus2Change($event,$login,$status2,$oldstatus2){
	global $_debug;
	if($_debug>0) console("um.Event[$event]('$login',$status2)");
}

// PlayerShowML($event,$login,$ShowML): player ShowML has changed
// (from manialinks plugin event)
function umPlayerShowML($event,$login,$ShowML){
	global $_debug;
	if($_debug>0) console("um.Event[$event]('$login',".($ShowML?'true':'false').")");
}

// PlayerDisconnect($event,$login): player has gone
// (from TrackMania.PlayerDisconnect callback, or Fast simulated)
function umPlayerDisconnect($event,$login){
	global $_debug;
	if($_debug>0) console("um.Event[$event]('$login')");
}

// PlayerRemove($event,$login,$fully): player is removed from list
// (Fast players plugin event)
function umPlayerRemove($event,$login,$fully){
	global $_debug;
	if($_debug>0) console("um.Event[$event]('$login',$fully)");
}

// BeforeEndRound($event,$delay,$time)
// (from TrackMania.ManualFlowControlTransition callback)
// need ManualFlowControl to be activated for script !!! ie only if global $_use_flowcontrol == true
//  Transition 'Play -> Synchro' : before StatusChanged 4->3 and EndRound, after all PlayerFinish
//  Transition 'Play -> Podium' : before StatusChanged 4->5 and EndRound and EndRace,
// $delay is milliseconds since callback was received. Can delay transition using: delayTransition($delay);
// $time is the script time at TrackMania.ManualFlowControlTransition callback
// The transition method is called a first time with delay 0, then eventually other times
// and called a last time with delay -1 just before to proceed transition (where delayTransition($delay) will have no effect)
// time is the original time when transition was received
function umBeforeEndRound($event,$delay,$time){
	global $_debug;
	if($_debug>0) console("um.Event[$event]($delay,$time)");
}

// EndRound($event,$Ranking,$ChallengeInfo,$GameInfos,$SpecialRestarting): end of the round
// (from TrackMania.EndRound callback, was previously a Fast event)
// $SpecialRestarting is true if special round restart (ie falsestart), same as $_players_round_restarting
function umEndRound($event,$Ranking,$ChallengeInfo,$GameInfos,$SpecialRestarting){
	global $_debug;
	if($_debug>0) console("um.Event[$event]");
}

// EndRace($event,$Ranking,$ChallengeInfo,$GameInfos,$continuecup,$warmup,$fwarmup)
// (from TrackMania.EndRace callback)
function umEndRace($event,$Ranking,$ChallengeInfo,$GameInfos,$continuecup,$warmup,$fwarmup){
	global $_debug;
	if($_debug>0) console("um.Event[$event]($continuecup,$warmup,$fwarmup)");
}

// EndMatch($event,$match_map,$players_round_current,$maxscore,$match_scores,$match_config): called at end of match
// (from match plugin)
function umEndMatch($event,$match_map,$players_round_current,$maxscore,$match_scores,$match_config){
	global $_debug;
	if($_debug>0) console("um.Event[$event]($match_map,$players_round_current,$maxscore)");
}

// EndResult($event): result table/podium at end of race
// (Fast event)
function umEndResult($event){
	global $_debug;
	if($_debug>0) console("um.Event[$event]");
}

// EndPodium($event,$delay,$time)
// (from TrackMania.ManualFlowControlTransition callback)
// need ManualFlowControl to be activated for script !!! ie only if global $_use_flowcontrol == true
//  Transition 'Podium -> Synchro' : before StatusChanged 5->2 and BeginRace, seconds after EndRace
// $delay is milliseconds since callback was received. Can delay transition using: delayTransition($delay);
// $time is the script time at TrackMania.ManualFlowControlTransition callback
// The transition method is called a first time with delay 0, then eventually other times
// and called a last time with delay -1 just before to proceed transition (where delayTransition($delay) will have no effect)
// time is the original time when transition was received
function umEndPodium($event,$delay,$time){
	global $_debug;
	if($_debug>0) console("um.Event[$event]($delay,$time)");
}

// FinishRace($event,$tm_db_n,$chalinfo,$ChallengeInfo): $chalinfo is the array returned by the database server
// (Fast database plugin event)
function umFinishRace($event,$tm_db_n,$chalinfo,$ChallengeInfo){
	global $_debug;
	if($_debug>0) console("um.Event[$event]");
}

// RoundCustomPointsChange($event,$custompoints) : the RoundCustomPoints has changed
// (Fast event)
function umRoundCustomPointsChange($event,$custompoints){
	global $_debug;
	if($_debug>0) console("um.Event[$event](".implode(',',$custompoints).")");
}


// TunnelDataReceived($event,$login,$data)
// (from TrackMania.TunnelDataReceived callback)
function umTunnelDataReceived($event,$login,$data){
	global $_debug;
	if($_debug>0) console("um.Event[$event]($login)");
}

// StoreInfos(): called regulary to build stored infos (for restore system)
//       add infos to store in global $_StoredInfos array
function umStoreInfos($event){
	global $_StoredInfos,$_debug;
	if($_debug>0) console("um.Event[$event]");
}

// RestoreInfos($event,$restoretype,$liveage,$playerschanged,$rankingchanged):
//       called at beginning (after ServerStart) to restore scripts infos from global $_StoredInfos array
//       $restoretype='previous' if want to restore old state (ie maps,name,pass) from previous use
//       $restoretype='live' if want to restore after crash/quick script restart (but the dedicated stayed alive)
//       $restoretype='start' if normal start without restoring previous values
//         $liveage=seconds since last keepalive (-1 if dedicated was restarted !)
//         $playerschanged=true if playerlist has changed
//         $rankingchanged=true if rankinglist has changed
function umRestoreInfos($event,$restoretype,$liveage,$playerschanged,$rankingchanged){
	global $_StoredInfos,$_debug;
	if($_debug>0) console("um.Event[$event]");
}

?>
