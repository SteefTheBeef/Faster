<?php
////////////////////////////////////////////////////////////////
//¤
// Date:      03.2026
// Author:    [TnT]BlackCat
//
////////////////////////////////////////////////////////////////
// uncomment the next line to activate the um plugin and see all events in log !
require_once "helpers/replay/ReplayManager.php";

registerPlugin('umReplays',41);

function umReplaysEndRace($event,$Ranking,$ChallengeInfo,$GameInfos,$continuecup,$warmup,$fwarmup){
	$replayManager = new ReplayManager("/TMF08347/Tracks/Replays/UM");
	$replayManager->saveBetterReplay($event, $Ranking, $ChallengeInfo, $GameInfos);
}

?>