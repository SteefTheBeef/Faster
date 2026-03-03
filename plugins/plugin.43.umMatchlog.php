<?php
require_once "helpers/challenge/challenge.php";
require_once "helpers/utils/StringUtils.php";
require_once "helpers/matchlog/utils/MatchlogUtils.php";
require_once "helpers/matchlog/utils/MatchlogConsole.php";
require_once "helpers/matchlog/MatchlogTeams.php";
require_once "helpers/matchlog/MatchlogLaps.php";
require_once "helpers/matchlog/MatchlogRounds.php";
require_once "helpers/matchlog/MatchlogStunts.php";
require_once "helpers/matchlog/MatchlogTimeAttack.php";
require_once "helpers/matchlog/Matchlog.php";

require_once "helpers/utils/XmlTag.php";
require_once "helpers/utils/MLState.php";
require_once "helpers/utils/StringUtils.php";
require_once "helpers/utils/Arrays.php";
require_once "helpers/um/UMPanel.php";

require_once "helpers/um/storage/MatchlogFileParser.php";
require_once "helpers/um/storage/UmPlayers.php";
require_once "helpers/um/storage/BestRaces.php";
require_once "helpers/um/storage/Donations.php";
require_once "helpers/um/storage/utils/FastFile.php";
require_once "helpers/um/storage/utils/CsvFile.php";

////////////////////////////////////////////////////////////////
//
// Date:      02.2026
// Author:    [TnT]BlackCat
//
////////////////////////////////////////////////////////////////

registerPlugin('umMatchlog',43,1.0);

//--------------------------------------------------------------
// BeginRace :
//--------------------------------------------------------------
function umMatchlogBeginRace($event,$GameInfos){
	global $_GameInfos, $_Ranking;
    Matchlog::create("BEGIN_RACE", $_GameInfos['GameMode'], null, $_Ranking);
}

//------------------------------------------
// EndRound :
//------------------------------------------
function umMatchlogEndRound($event,$Ranking,$ChallengeInfo,$GameInfos,$SpecialRestarting){
	if($SpecialRestarting || isMatchlogDisabled()){
		return;
	}

	Matchlog::create("END_ROUND", $GameInfos["GameMode"], $ChallengeInfo, $Ranking);
}

//------------------------------------------
// RaceFinish
//------------------------------------------
function umMatchlogEndRace($event,$Ranking,$ChallengeInfo,$GameInfos){
	if(isMatchlogDisabled()) {
		return;
	}

	Matchlog::create("END_RACE", $GameInfos["GameMode"], $ChallengeInfo, $Ranking);
}

//------------------------------------------
// write in match log with time
//------------------------------------------

function isMatchlogDisabled() {
	global $_WarmUp,$_FWarmUp;
	return $_WarmUp || $_FWarmUp > 0;
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
?>
