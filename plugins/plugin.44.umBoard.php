<?php
////////////////////////////////////////////////////////////////
//
// Date:      02.2026
// Author:    [TnT]BlackCat
// 
////////////////////////////////////////////////////////////////

// Layout
require_once "helpers/um/layout/LayoutGeometry.php";
require_once "helpers/um/layout/LayoutGeometryMini.php";
require_once "helpers/um/layout/LayoutMarkup.php";
require_once "helpers/um/layout/LayoutTheme.php";
require_once "helpers/um/layout/Layout.php";

// Utils
require_once "helpers/utils/XmlTag.php";
require_once "helpers/utils/MLState.php";
require_once "helpers/utils/StringUtils.php";
require_once "helpers/utils/Arrays.php";
require_once "helpers/um/UMPanel.php";

// Services
require_once "helpers/um/services/QualificationRankingService.php";

// Storage
require_once "helpers/um/storage/MatchlogFileParser.php";
require_once "helpers/um/storage/UmPlayers.php";
require_once "helpers/um/storage/BestRaces.php";
require_once "helpers/um/storage/Donations.php";
require_once "helpers/um/storage/utils/FastFile.php";
require_once "helpers/um/storage/utils/CsvFile.php";

// Domain
require_once "helpers/um/domain/UMConfigEntry.php";
require_once "helpers/um/domain/UMConfig.php";
require_once "helpers/um/domain/UmMap.php";
require_once "helpers/um/domain/UmPanelKeys.php";
require_once "helpers/um/domain/UmState.php";
require_once "helpers/um/domain/PrizePool.php";

// General Components
require_once "helpers/um/components/BoardTitle.php";
require_once "helpers/um/components/BoardBorders.php";
require_once "helpers/um/components/OpenCloseToggle.php";
require_once "helpers/um/components/BackgroundPanel.php";
require_once "helpers/um/components/Tabs.php";
require_once "helpers/um/components/SubTabs.php";
require_once "helpers/um/components/Table.php";

// Left panel components
require_once "helpers/um/components/panels/left/PlayerListPlayoffsPanel.php";
require_once "helpers/um/components/panels/left/QualiPlayerListPanelBuilder.php";
require_once "helpers/um/components/panels/left/PlayerPagination.php";

// Right panel components
require_once "helpers/um/components/panels/right/TableBuilder.php";
require_once "helpers/um/components/panels/right/RightPanel.php";
require_once "helpers/um/components/panels/right/InformationPanelBuilder.php";
require_once "helpers/um/components/panels/right/QualificationPanelBuilder.php";
require_once "helpers/um/components/panels/right/PlayerRacesPanel.php";
require_once "helpers/um/components/panels/right/RulesPanelBuilder.php";
require_once "helpers/um/components/panels/right/SchedulePanelBuilder.php";
require_once "helpers/um/components/panels/right/LeaderboardPanel.php";
require_once "helpers/um/components/panels/right/LeaderboardPlayersTable.php";
require_once "helpers/um/components/panels/right/PlayerDetailsTable.php";
require_once "helpers/um/components/panels/right/EnviLeaderboardPlayerPanel.php";
require_once "helpers/um/components/panels/right/PlayerEnviDetailsTable.php";
require_once "helpers/um/components/panels/right/PrizePoolPanel.php";

// Main component
require_once "helpers/um/components/UmBoard.php";
require_once "helpers/um/components/UmPanelRenderContext.php";

registerPlugin(UmPanelKeys::ML_ID_PANEL, 44, 1.0);


//--------------------------------------------------------------
// Init : (plugin init)
//--------------------------------------------------------------
function umBoardInit($event) {
    global $_ml_act, $umConfig, $qualiBestRacesConfig, $qualiBestLapsConfig, $umState, $selectPlayerActionIds, $layout;

    $layout = Layout::build();
    $umConfig = new UMConfig();
    $qualiBestRacesConfig = $umConfig->um4QualiBestRace;
    $qualiBestLapsConfig = $umConfig->um4QualiBestLap;
    $umState = new UmState($qualiBestRacesConfig, $qualiBestLapsConfig);

    console("Init umBoard");
    computeRankings();

    // get a unique manialink id. Use the same name as for
    // manialinksAddManialink(). It will add its value automatically in <manialink id='xx'>
    manialinksAddId(UmPanelKeys::ML_ID_PANEL);

    $selectPlayerActionIds = array();
    for ($i = 0; $i < 16; $i++) {
        $actionName = UmPanelKeys::createPlayerSelectActionString($i);
        manialinksAddAction($actionName);                 // creates a unique action id
        $selectPlayerActionIds[$i] = $_ml_act[$actionName]; // numeric action id stored by manialinks plugin
    }

   // $umScoreBoardPlayers = MatchlogFileParser::getScoreboardPlayersFromMatchlog('fastlog/um3_semi.txt', $umConfig->um3Semi->pointsDistribution);

    $actions = UmPanelKeys::actionsToRegister();
    $count = count($actions);
    for ($i = 0; $i < $count; $i++) {
        manialinksAddAction($actions[$i]);
    }
}

//--------------------------------------------------------------
// PlayerConnect : (event from server callback)
//--------------------------------------------------------------
function umBoardPlayerConnect($event, $login) {
    global $umState;
    $umState = (object)$umState;

    $umState->playerConnect($login);
    umBoardUpdateXml($login, 'show');
    addCall(null,'SetCallVoteTimeOut',0);
}
function umBoardPlayerDisconnect($event,$login){
    global $umState;
    $umState = (object)$umState;
    $umState->playerDisconnect($login);
}

function umBoardEndRace($event, $Ranking, $ChallengeInfo, $GameInfos, $continuecup, $warmup, $fwarmup) {
    global $_players;
    computeRankings();

    foreach ($_players as $login => &$pl) {
        umBoardUpdateXml($login, 'show');
    }
}
function umBoardBeginRace($event,$GameInfos,$ChallengeInfo,$newcup,$warmup,$fwarmup){
   //computeRankings();
}
//--------------------------------------------------------------
// PlayerShowML : (event from manialink plugin when the player set it on/off)
//--------------------------------------------------------------
function umBoardPlayerShowML($event, $login, $ShowML) {
    if ($ShowML > 0)
        umBoardUpdateXml($login, 'show');
}


//--------------------------------------------------------------
// PlayerManialinkPageAnswer : (event from server callback)
//--------------------------------------------------------------
function umBoardPlayerManialinkPageAnswer($event, $login, $answer, $action) {
    UmBoard::handleAction($login, $action);
    umBoardUpdateXml($login, 'show');
}

//--------------------------------------------------------------
// Function called to handle the manialink drawing
// action can be 'show', 'refresh', 'hide', 'remove'
//--------------------------------------------------------------
function umBoardUpdateXml($login, $action = 'show') {
    global $_players, $selectPlayerActionIds, $_ml_act, $umConfig, $umState, $layout;

    // if the players disabled manialinks then do nothing
    if (!isset($_players[$login]['ML']['ShowML']) || $_players[$login]['ML']['ShowML'] <= 0)
        return;

    $ctx = new UmPanelRenderContext($login, $layout, $selectPlayerActionIds, $_ml_act, $umConfig, $umState);
    $xml = UmBoard::buildPanelXml($ctx);

    manialinksSet($login, UmPanelKeys::ML_ID_PANEL, $action, $xml);
}

function computeRankings() {
    global $umState;
    $umState = (object)$umState;
    $umState->computeRankings();
    $donations = Donations::loadDonations();
    $umState->setDonations($donations);
}

?>
