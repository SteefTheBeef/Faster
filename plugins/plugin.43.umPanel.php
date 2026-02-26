<?php
////////////////////////////////////////////////////////////////
//¤
// File:      FAST 3.2 (First Automatic Server for Trackmania)
// Web:       
// Date:      01.06.2008
// Author:    Gilles Masson
// 
////////////////////////////////////////////////////////////////
//
// needed plugins: manialinks
//
// This is a simple plugin showing how manialinks can be used with Fast.
// ml_mapinfo plugin is another rather simple example.
// See dev documentation for more infos.
//
// Just uncomment the registerPlugin() function to make it active

// Layout
require_once "helpers/um/layout/LayoutGeometry.php";
require_once "helpers/um/layout/LayoutMarkup.php";
require_once "helpers/um/layout/LayoutTheme.php";
require_once "helpers/um/layout/Layout.php";

// Utils
require_once "helpers/utils/XmlTag.php";
require_once "helpers/utils/MLState.php";
require_once "helpers/utils/StringUtils.php";
require_once "helpers/um/UMPanel.php";

// Services
require_once "helpers/um/services/QualificationRankingService.php";

// Storage
require_once "helpers/um/storage/MatchlogFileParser.php";
require_once "helpers/um/storage/UmPlayers.php";
require_once "helpers/um/storage/BestRaces.php";
require_once "helpers/um/storage/utils/FastFile.php";
require_once "helpers/um/storage/utils/CsvFile.php";

// Domain
require_once "helpers/um/domain/UMConfigEntry.php";
require_once "helpers/um/domain/UMConfig.php";
require_once "helpers/um/domain/UmMap.php";
require_once "helpers/um/domain/UmPanelKeys.php";
require_once "helpers/um/domain/UmState.php";

// General Components
require_once "helpers/um/components/BoardTitle.php";
require_once "helpers/um/components/BoardBorders.php";
require_once "helpers/um/components/OpenCloseToggle.php";
require_once "helpers/um/components/BackgroundPanel.php";
require_once "helpers/um/components/Tabs.php";
require_once "helpers/um/components/SubTabs.php";

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

// Main component
require_once "helpers/um/components/UmBoard.php";
require_once "helpers/um/components/UmPanelRenderContext.php";

registerPlugin(UmPanelKeys::ML_ID_PANEL, 43, 1.0);


//--------------------------------------------------------------
// Init : (plugin init)
//--------------------------------------------------------------
function umPanelInit($event) {
    global $_ml_act, $umScoreBoardPlayerActions, $umScoreBoardSelectedPlayerRow, $umConfig, $qualiBestRacesConfig, $qualiBestLapsConfig, $umState, $umScoreBoardPlayers, $selectedPlayer;

    $umConfig = new UMConfig();
    $qualiBestRacesConfig = $umConfig->um4QualiBestRace;
    $qualiBestLapsConfig = $umConfig->um4QualiBestLap;
    $umState = new UmState($qualiBestRacesConfig, $qualiBestLapsConfig);

    $umScoreBoardSelectedPlayerRow = array();
    // here we store the player's race results'
    $selectedPlayer = array();

    // get a unique manialink id. Use the same name as for
    // manialinksAddManialink(). It will add its value automatically in <manialink id='xx'>
    manialinksAddId(UmPanelKeys::ML_ID_PANEL);

    $umScoreBoardPlayerActions = array();
    for ($i = 0; $i < 16; $i++) {
        $actionName = UmPanelKeys::scoreboardRowAction($i);
        manialinksAddAction($actionName);                 // creates a unique action id
        $umScoreBoardPlayerActions[$i] = $_ml_act[$actionName]; // numeric action id stored by manialinks plugin
    }

    $umScoreBoardPlayers = MatchlogFileParser::getScoreboardPlayersFromMatchlog('fastlog/um3_semi.txt', $umConfig->um3Semi->pointsDistribution);


    $actions = UmPanelKeys::actionsToRegister();
    $count = count($actions);
    for ($i = 0; $i < $count; $i++) {
        manialinksAddAction($actions[$i]);
    }
}

function computeRankings($login) {
    global $umState;
    $umState = (object)$umState;
    $umState->computeRankings();
    $umState->playerConnect($login);
}

//--------------------------------------------------------------
// PlayerConnect : (event from server callback)
//--------------------------------------------------------------
function umPanelPlayerConnect($event, $login) {
    global $_players, $umState, $umScoreBoardPlayers, $umScoreBoardSelectedPlayerRow, $selectedPlayer, $qualiBestRacesConfig, $qualiBestLapsConfig;

    computeRankings($login);

    // Default: panel open
    if (!isset($_players[$login]['ML'][UmPanelKeys::ML_PANEL_CLOSED])) {
        $_players[$login]['ML'][UmPanelKeys::ML_PANEL_CLOSED] = 0; // 0=open, 1=closed
    }

    if (!isset($_players[$login]['ML'][UmPanelKeys::ML_RACES_PAGE])) {
        $_players[$login]['ML'][UmPanelKeys::ML_RACES_PAGE] = 0; // 0-based page index
    }

    // Default tab (store action name)
    if (!isset($_players[$login]['ML'][UmPanelKeys::ML_TAB]) || $_players[$login]['ML'][UmPanelKeys::ML_TAB] === '') {
        $_players[$login]['ML'][UmPanelKeys::ML_TAB] = UmPanelKeys::ACT_TAB_SEMI_FINAL;
    }

    umPanelUpdateXml($login, 'show');
}


//--------------------------------------------------------------
// PlayerShowML : (event from manialink plugin when the player set it on/off)
//--------------------------------------------------------------
function umPanelPlayerShowML($event, $login, $ShowML) {
    global $_mldebug;
    if ($_mldebug > 0) console("ml_howto.Event[$event]('$login',$ShowML)");

    if ($ShowML > 0)
        umPanelUpdateXml($login, 'show');
}


//--------------------------------------------------------------
// PlayerManialinkPageAnswer : (event from server callback)
//--------------------------------------------------------------
function umPanelPlayerManialinkPageAnswer($event, $login, $answer, $action) {
    UmBoard::handleAction($login, $action);
    umPanelUpdateXml($login, 'show');
}

//--------------------------------------------------------------
// Function called to handle the manialink drawing
// action can be 'show', 'refresh', 'hide', 'remove'
//--------------------------------------------------------------
function umPanelUpdateXml($login, $action = 'show') {

    global $_mldebug, $_ml_act, $_players, $_ml_howto_force;
    // if the players disabled manialinks then do nothing
    if (!isset($_players[$login]['ML']['ShowML']) || $_players[$login]['ML']['ShowML'] <= 0)
        return;

    $xml = getUMPanelXml($login);
    manialinksSet($login, UmPanelKeys::ML_ID_PANEL, $action, $xml);
}

function getUMPanelXml($login) {
    global $umScoreBoardPlayerActions, $umScoreBoardSelectedPlayerRow, $umScoreBoardPlayers, $selectedPlayer;
    global $_ml_act, $umConfig, $umState;

    $umState = (object)$umState;
    $layout = Layout::build();

    $players = $umScoreBoardPlayers;
    $selectedRow = isset($umScoreBoardSelectedPlayerRow[$login]) ? (int)$umScoreBoardSelectedPlayerRow[$login] : -1;

    $ctx = new UmPanelRenderContext(
        $login,
        $layout,
        $players,
        $selectedRow,
        isset($selectedPlayer[$login]) ? $selectedPlayer[$login] : null,
        $umScoreBoardPlayerActions,
        $_ml_act,
        $umConfig,
        $umState
    );

    return UmBoard::buildPanelXml($ctx);
}

function umPanelPlayerMenuBuild($event, $login) {
    global $_mldebug;
    if ($_mldebug > 0) console("ml_howto.Event[$event]('$login')");

    // add a new menu 'menu.howto' in 'menu.main' :
    $menuitem = array('Name' => 'Howto ...', 'Menu' => array('DefaultStyles' => true, 'Width' => 13, 'Items' => array()));
    $menuitem['Menu']['Items']['menu.howto.item1'] = array('Name' => 'Item1', 'Type' => 'item');
    $menuitem['Menu']['Items']['menu.howto.hide1'] = array('Name' => 'Hide1', 'Type' => 'hide');
    $menuitem['Menu']['Items']['menu.howto.bool1'] = array('Name' => 'Bool1', 'Type' => 'bool');
    $menuitem['Menu']['Items']['menu.howto.bool2'] = array('Name' => array('Bool2:on', 'Bool2:off'), 'Type' => 'bool', 'State' => false);
    $menuitem['Menu']['Items']['menu.howto.multi1'] = array('Name' => array('Multi:1', 'Multi:2', 'Multi:3'), 'Type' => 'multi');
    $menuitem['Menu']['Items']['menu.howto.menu1'] = array('Name' => 'Menu1', 'Menu' => array('Items' => array()));
    ml_menusAddItem($login, 'menu.main', 'menu.howto', $menuitem);
    // add an item in submenu 'menu.howto.menu1' :
    ml_menusAddItem($login, 'menu.howto.menu1', 'menu.howto.item2', array('Name' => 'Item2', 'Type' => 'item'));
    ml_menusAddItem($login, 'menu.howto.menu1', 'menu.howto.hide2', array('Name' => 'Hide2', 'Type' => 'hide'));

    // add also 'menu.howto.bool1' in Hud :
    ml_menusAddItem($login, 'menu.hud', 'menu.howto.bool1', array('Name' => 'Bool1', 'Type' => 'bool'));


    // make a new menu elsewhere, at bottom right :
    $menu2 = array('Show' => true, 'X' => 64, 'Y' => -48,
        'Pos' => 'bottom', 'SubPos' => 'right',
        'DefaultStyles' => true, 'Width' => 13, 'Items' => array());
    $menu2['Items']['menu.htmenu.item2'] = array('Name' => 'Item2');
    $menu2['Items']['menu.htmenu.item3'] = array('Name' => 'Item3');
    ml_menusNewMenu($login, 'menu.htmenu', $menu2);
    // add a submenu, items with same idname are shared ! submenus can't be shared, only simple items !!
    $menuitem2 = array('Name' => 'Howto2 ...', 'Menu' => array('DefaultStyles' => true, 'Width' => 13, 'Items' => array()));
    $menuitem2['Menu']['Items']['menu.howto.item1'] = array('Name' => 'Item1', 'Type' => 'item');
    $menuitem2['Menu']['Items']['menu.howto.hide1'] = array('Name' => 'Hide1', 'Type' => 'hide');
    $menuitem2['Menu']['Items']['menu.howto2.hide2'] = array('Name' => 'Hide2', 'Type' => 'hide');
    $menuitem2['Menu']['Items']['menu.howto.bool1'] = array('Name' => 'Bool1', 'Type' => 'bool');
    $menuitem2['Menu']['Items']['menu.howto.bool2'] = array('Name' => array('Bool2:on', 'Bool2:off'), 'Type' => 'bool', 'State' => false);
    $menuitem2['Menu']['Items']['menu.howto.multi1'] = array('Name' => array('Multi:1', 'Multi:2', 'Multi:3'), 'Type' => 'multi');
    $menuitem2['Menu']['Items']['menu.howto2.menu2'] = array('Name' => 'Menu2', 'Menu' => array('Items' => array()));
    $menuitem2['Menu']['Items']['menu.howto2.menu2']['Menu']['Items']['menu.howto2.menu2.item1'] = array('Name' => 'Item1', 'Type' => 'item');
    ml_menusAddItem($login, 'menu.htmenu', 'menu.howto2', $menuitem2);
    ml_menusAddItem($login, 'menu.howto2.menu2', 'menu.howto2.menu2.item2', array('Name' => 'Item2', 'Type' => 'item'));
    // show the new menu
    ml_menusShowMenu($login, 'menu.htmenu');
}

//--------------------------------------------------------------
// PlayerMenuAction : (event from ml_menus plugin)
//--------------------------------------------------------------
function umPanelPlayerMenuAction($event, $login, $action, $state) {
    global $_mldebug;
    if ($_mldebug > 0) console("ml_howto.Event[$event]('$login',$action,$state)");

    if ($action == 'menu.howto.item1') {
        addCall(null, 'ChatSendToLogin', "HowTo: \$fff$action  $state", $login);
    } elseif ($action == 'menu.howto.hide1') {
        addCall(null, 'ChatSendToLogin', "HowTo: \$fff$action = $state", $login);
    } elseif ($action == 'menu.howto.bool1') {
        addCall(null, 'ChatSendToLogin', "HowTo: \$fff$action = $state", $login);
    } elseif ($action == 'menu.howto.bool2') {
        addCall(null, 'ChatSendToLogin', "HowTo: \$fff$action = $state", $login);
    } elseif ($action == 'menu.howto.multi1') {
        addCall(null, 'ChatSendToLogin', "HowTo: \$fff$action = $state", $login);
    } elseif ($action == 'menu.howto.menu1') {
        addCall(null, 'ChatSendToLogin', "HowTo: \$fff$action = $state", $login);
    }
}

function umPanelEndRace($event, $Ranking, $ChallengeInfo, $GameInfos, $continuecup, $warmup, $fwarmup) {
   // umPanelUpdateXml("blackcat111", 'show');
}

?>
