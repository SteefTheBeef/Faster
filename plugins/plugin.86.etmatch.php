<?php
////////////////////////////////////////////////////////////////
//¤
// File:      FAST 3.2 (First Automatic Server for Trackmania)
// Web:       
// Date:      13.03.2011
// Author:    Gilles Masson
// 
////////////////////////////////////////////////////////////////
//
// wget -O teamlist.txt http://www.et-leagues.com/{$etcompetname}/api_teamlist.php
// wget -O playerlist.txt http://www.et-leagues.com/{$etcompetname}/api_playerlist.php
// if needed edit 'matches.php' for teams in playoff matches
// if needed edit 'prevresults.txt' with previous match scores, kind teamlaps match log, with fields [1]=score and [5]=ext id
// if needed edit 'teams_bonuses.txt' with previous match scores, kind teamlaps match log, with fields [1]=score and [5]=ext id
//
// before divisions matches pre-groups:   /et <matchconf-name> d <#div>
// before divisions matches main-groups:  /et <matchconf-name> d2 <#div>
// before playoff matches:                /et <matchconf-name> m <#match>
//
// FET6 example:
//  wget -O teamlist.txt http://www.et-leagues.com/fet6/api_teamlist.php
//  wget -O playerlist.txt http://www.et-leagues.com/fet6/api_playerlist.php
// (see mkservs_FET6 )
//
// mFET3 example:
// wget -O teamlist.txt http://www.et-leagues.com/minifet3/api_teamlist.php
// wget -O playerlist.txt http://www.et-leagues.com/minifet3/api_playerlist.php
// (see mkservs_mFET3 )
//
// http://www.et-generation.com/uploads/mappacks/gc9players.txt

global $_is_relay;
if(!$_is_relay) registerPlugin('etmatch',86,1.0);

// prepare a specific ET mass match

function etmatchInit($event){
	global $_debug,$_match_mode,$_etmatch_players,$_etmatch_teams,$_etmatch_div_id,$_etmatch_div2_id,$_etmatch_match_id;

	$_etmatch_players = array();
	$_etmatch_teams = array();
	$_etmatch_div_id = -1;
	$_etmatch_div2_id = -1;
	$_etmatch_match_id = -1;
}

function etmatchPrevScores(){
	global $_debug,$_players,$_match_mode,$_etmatch_players,$_etmatch_teams,$_etmatch_div_id,$_etmatch_div2_id,$_etmatch_match_id,$prevresults,$_fteams;
	foreach($_fteams as &$fteam){
		$extid = $fteam['ExtId'];
		if($extid >= 0 && isset($prevresults[$extid]) && $prevresults[$extid] > 0)
			$fteam['MatchPrevScore'] = $prevresults[$extid];
		else
			$fteam['MatchPrevScore'] = 0;
	}
	//console("etmatchPrevScores:: ".print_r($_fteams,true));
}

function etmatchBonuses(){
	global $_debug,$_players,$_match_mode,$_etmatch_players,$_etmatch_teams,$_etmatch_div_id,$_etmatch_div2_id,$_etmatch_match_id,$_etmatch_bonuses,$_fteams;
	foreach($_fteams as &$fteam){
		$extid = $fteam['ExtId'];
		if($extid >= 0 && isset($_etmatch_bonuses[$extid]) && $_etmatch_bonuses[$extid] > 0)
			$fteam['Bonus'] = $_etmatch_bonuses[$extid];
		else
			$fteam['Bonus'] = 0;
	}
	//console("etmatchBonuses:: ".print_r($_fteams,true));
}


function etmatchPlayerConnect($event,$login,$pinfo,$pdetailedinfo,$pranking){
	global $_debug,$_players,$_match_mode,$_etmatch_players,$_etmatch_teams,$_etmatch_div_id,$_etmatch_div2_id,$_etmatch_match_id,$prevresults,$_etmatch_bonuses,$_fteams,$_match_map;

	if(isset($_etmatch_players[$login]['teamid'])){
		$teamid = $_etmatch_players[$login]['teamid'];
		$ftid = fteamsAddPlayer(-$teamid,$login,true);
		if($ftid >= 0){
			$_etmatch_teams[$teamid]['ftid'] = $ftid;
			$_etmatch_players[$login]['ftid'] = $ftid;
			fteamsSetName($ftid,$_etmatch_teams[$teamid]['tag']);
			console("etmatchPlayerConnect:: {$login} added in fteam {$ftid} ({$teamid})");

			console("etmatchPlayerConnect::[before prev] fteam {$ftid} ({$teamid}): ".print_r($_fteams[$ftid],true));

			// add previous match score in _fteams table
			if(isset($prevresults[$teamid]))
				$_fteams[$ftid]['MatchPrevScore'] = $prevresults[$teamid];
			else
				$_fteams[$ftid]['MatchPrevScore'] = 0;

			// add bonus score in _fteams table
			if(isset($_etmatch_bonuses[$teamid]))
				$_fteams[$ftid]['Bonus'] = $_etmatch_bonuses[$teamid];
			else
				$_fteams[$ftid]['Bonus'] = 0;

			console("etmatchPlayerConnect::[after prev] fteam {$ftid} ({$teamid}): {$_fteams[$ftid]}");

			fteamsUpdateTeampanelXml(true,'refresh');
			fteamsBuildScore(true);
			fgmodesUpdateScoretableXml(true,'refresh');

		}else{
			console("etmatchPlayerConnect:: failed to add {$login} in a fteam ({$ftid},{$teamid})");
		}

	}else if($_match_map > 0 && !verifyAdmin($login)){
		// not in a team nor admin, and match is started
		console("Match started ({$_match_map}) and not in team : ban {$login}");
		addCall(null,'Kick',''.$login,'Match is started and you are not in a team : go on relay please !');
		//addCall(null,'Ban',''.$login,'Match is started and you are not in a team : go on relay please !');
	}
}


function etmatchLoadTeams($etcompetname){
	global $_debug,$_players,$_match_map,$_fteams_rules,$_match_mode,$_match_config,$_etmatch_players,$_etmatch_teams,$_etmatch_matches,$_etmatch_div_id,$_etmatch_div2_id,$_etmatch_match_id,$_FGameMode,$_FGameModes,$prevresults,$_etmatch_bonuses;

	console("etmatchLoadTeams:: div={$_etmatch_div_id} , div2={$_etmatch_div2_id} , match={$_etmatch_match_id}");

	$_etmatch_matches = array(); 
	include('matches.php');
	// have $_etmatch_matches[matchid] = array(tid,tid,...)

	foreach($_etmatch_matches as $matchid => $tidlist){
		$tids = array();
		foreach($tidlist as $tid){
			$tids[$tid] = $tid;
		}
		$_etmatch_matches[$matchid] = $tids;
	}
	// converted to $_etmatch_matches[matchid] = array(tid=>tid,tid=>tid,...)

	// import matchlog lines : 'prevresults.txt' have previous match scores, [1]=score and [5]=ext id
	$prevresults = array();
	$presults = explode("\n",file_get_contents('prevresults.txt'));
	foreach($presults as $presult){
		$pres = explode(';',$presult);
		if(isset($pres[0])){
			$pres2 = explode(',',$pres[0]);
			if(isset($pres2[1]) && isset($pres2[5]) && $pres2[1] > 0 && $pres2[5] > 0){
				$prevresults[$pres2[5]] = $pres2[1]+0;
			}
		}
	}
	console("etmatchLoadTeams:: prevresults: ".print_r($prevresults,true));

	// import matchlog lines : 'teams_bonuses.txt' have previous match scores, [1]=score and [5]=ext id
	$_etmatch_bonuses = array();
	$presults = explode("\n",file_get_contents('teams_bonuses.txt'));
	foreach($presults as $presult){
		$pres = explode(';',$presult);
		if(isset($pres[0])){
			$pres2 = explode(',',$pres[0]);
			if(isset($pres2[1]) && isset($pres2[5]) && $pres2[1] > 0 && $pres2[5] > 0){
				$_etmatch_bonuses[$pres2[5]] = $pres2[1]+0;
			}
		}
	}
	console("etmatchLoadTeams:: _etmatch_bonuses: ".print_r($_etmatch_bonuses,true));

	// some setup if match is not started
	if($_match_map < 0 && isset($_match_config[$_match_mode]['FGameMode'])){
		$fgamemode = $_match_config[$_match_mode]['FGameMode'];

		// change gamemode if needed
		if($fgamemode != $_FGameMode){
			console("etmatchLoadTeams:: change to fgamemode: {$_FGameMode} -> {$_match_config[$_match_mode]['FGameMode']}");
			setNextFGameMode($_match_config[$_match_mode]['FGameMode']);
			addCall(null,'NextChallenge');
		}
		// few settings from match config
		console("etmatchLoadTeams:: {$_match_mode}, set lockmode etc.");
		foreach($_match_config[$_match_mode] as $conf => $val){
			if(isset($_FGameModes[$fgamemode][$conf]))
				$_FGameModes[$fgamemode][$conf] = $val;
			if(isset($_fteams_rules[$conf]))
				$_fteams_rules[$conf] = $val;
		}
		if(isset($_match_config[$_match_mode]['FreePlay']) && is_array($_match_config[$_match_mode]['FreePlay'])){
			foreach($_match_config[$_match_mode]['FreePlay'] as $conf => $val){
				if(isset($_FGameModes[$fgamemode][$conf]))
					$_FGameModes[$fgamemode][$conf] = $val;
				if(isset($_fteams_rules[$conf]))
					$_fteams_rules[$conf] = $val;
			}
		}
		console("etmatchLoadTeams:: FGameModes[{$fgamemode}]: ".print_r($_FGameModes[$fgamemode],true));
	}
	console("etmatchLoadTeams:: fteams_rules: ".print_r($_fteams_rules,true));
	console("etmatchLoadTeams:: match_config[{$_match_mode}]: ".print_r($_match_config[$_match_mode],true));

	fteamsClearAllTeams();

	//$xmlteamlist = file_get_contents("http://www.et-leagues.com/{$etcompetname}/api_teamlist.php");
	$xmlteamlist = file_get_contents('teamlist.txt');
	$xmlteamlist = str_replace('&', '&amp;',str_replace('&amp;', '&',$xmlteamlist));
	//print_r($xmlteamlist);
	$teamlist = xml_parse_string($xmlteamlist);
	//print_r($teamlist);
	
	// build teamlist array
	$_etmatch_teams = array();
	if(isset($teamlist['teamlist']['team']['.multi_same_tag.'])){
		unset($teamlist['teamlist']['team']['.multi_same_tag.']);
		foreach($teamlist['teamlist']['team'] as $id => $team){
			if(isset($team['id']) && isset($team['tag'])){
				$team['ftid'] = -1;

				if($_etmatch_div_id >= 0 && $team['division'] == $_etmatch_div_id){
					$team['match'] = -1;
					$_etmatch_teams[$team['id']] = $team;

				}elseif($_etmatch_div2_id >= 0 && $team['division2'] == $_etmatch_div2_id){
					$team['match'] = -1;
					$_etmatch_teams[$team['id']] = $team;

				}elseif($_etmatch_match_id >= 0 && isset($_etmatch_matches[$_etmatch_match_id][$team['id']])){
					$team['match'] = $_etmatch_match_id;
					$_etmatch_teams[$team['id']] = $team;
				}

			}else{
				console("etmatchLoadTeams:: uncomplete team: ",print_r($team,true));
			}
		}
	}

	//$xmlteamlist = file_get_contents("http://www.et-leagues.com/{$etcompetname}/api_playerlist.php");
	$xmlplayerlist = file_get_contents('playerlist.txt');
	$xmlplayerlist = str_replace('&', '&amp;',str_replace('&amp;', '&',$xmlplayerlist));
	//print_r($xmlplayerlist);
	$playerlist = xml_parse_string($xmlplayerlist);
	//print_r($playerlist);
	$_etmatch_players = array();
	
	// build playerlist array
	$_etmatch_players = array();
	if(isset($playerlist['playerlist']['player']['.multi_same_tag.'])){
		unset($playerlist['playerlist']['player']['.multi_same_tag.']);
		foreach($playerlist['playerlist']['player'] as $id => $player){
			if(isset($player['id']) && isset($player['login']) && isset($player['teamid'])){
				$login = trim(strtolower($player['login']));
				$teamid = $player['teamid'];
				if(isset($_etmatch_teams[$teamid])){
					$player['ftid'] = -1;
					$player['login'] = $login;
					$_etmatch_players[$login] = $player;
					$_etmatch_teams[$teamid]['players'][$login] = $login;

					// add to a team if active
					if(isset($_players[$login]['Active']) && $_players[$login]['Active']){
						$ftid = fteamsAddPlayer(-$teamid,$login,true);
						if($ftid >= 0){
							$_etmatch_teams[$teamid]['ftid'] = $ftid;
							$_etmatch_players[$login]['ftid'] = $ftid;
							fteamsSetName($ftid,$_etmatch_teams[$teamid]['tag']);
							console("etmatchLoadTeams:: {$login} added in fteam {$ftid} ({$teamid})");
						}else{
							console("etmatchLoadTeams:: failed to add {$login} in a fteam ({$ftid},{$teamid})");
						}
					}

				}else{
					//console("etmatchLoadTeams:: not existing team for player: ",print_r($player,true));
				}
			}else{
				console("etmatchLoadTeams:: uncomplete player: ",print_r($player,true));
			}
		}
	}

	//print_r($_etmatch_players);
	print_r($_etmatch_teams);
	file_put_contents("{$etcompetname}.d{$_etmatch_div_id}.{$_etmatch_div2_id}.m{$_etmatch_match_id}.teamlist.txt",	print_r($_etmatch_teams,true));

	if($_etmatch_match_id >= 0)
		console("etmatchLoadTeams:: etmatch_matches ({$_etmatch_match_id}): ".print_r($_etmatch_matches));

	console("etmatchLoadTeams:: {$etcompetname}: ".count($_etmatch_players)." players in ".count($_etmatch_teams)." teams !");

	etmatchPrevScores();
	etmatchBonuses();

	fteamsUpdateTeampanelXml(true,'refresh');
	fteamsBuildScore(true);
	fgmodesUpdateScoretableXml(true,'refresh');

	// force players not in team to spec
	foreach($_players as $login => &$pl){
		if($pl['Active'] && $pl['FTeamId'] < 0){
			addCall(null,'ForceSpectator',''.$login,1);
		}
	}
}


function chat_et($author, $login, $params1, $params){
	global $_debug,$_is_relay,$_match_config,$_match_map,$_match_mode,$_etmatch_players,$_etmatch_teams,$_etmatch_div_id,$_etmatch_div2_id,$_etmatch_match_id;
	
	if(!verifyAdmin($login)){
		$msg = localeText(null,'server_message').localeText(null,'interact').'need admin permissions !';
		addCall(null,'ChatSendToLogin', $msg, $login);
	}

	if($_is_relay)
		return;

	if(isset($params[0]) && $params[0] != ''){
		if($params[0] == 'clearall'){
			$_etmatch_teams = array();
			$_etmatch_players = array();
			fteamsClearAllTeams();
	
		}else{
			if(!isset($_match_config[$params[0]]['GameMode'])){
				matchReadConfigs('plugins/match.configs.xml.txt');
				matchReadConfigs('custom/match.configs.custom.xml.txt');
			}
			if(!isset($_match_config[$params[0]]['GameMode'])){
				$modelist = implode(',',array_keys($_match_config));
				$msg = localeText(null,'server_message').localeText(null,'interact')."Match mode {$params[0]} unknown ! ({$modelist})";
				addCall(null,'ChatSendToLogin', $msg, $login);
				
			}else{
				$_match_mode = $params[0];

				if(isset($params[2])){
					$_etmatch_div_id = -1;
					$_etmatch_div2_id = -1;
					$_etmatch_match_id = -1;

					if($params[1] == 'div' || $params[1] == 'd')
						$_etmatch_div_id = $params[2] + 0;

					else if($params[1] == 'div2' || $params[1] == 'd2')
						$_etmatch_div2_id = $params[2] + 0;

					else if($params[1] == 'match' || $params[1] == 'm')
						$_etmatch_match_id = $params[2] + 0;
				}

				etmatchLoadTeams($_match_mode);
			}
		}

	}else{
		$msg = localeText(null,'server_message').localeText(null,'interact')."/et gamename, clearall";
		addCall(null,'ChatSendToLogin', $msg, $login);
	}

}


?>
