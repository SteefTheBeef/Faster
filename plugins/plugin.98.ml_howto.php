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
require_once "helpers/matchlog/MatchlogFileParser.php";
require_once "helpers/um/UMState.php";
require_once "helpers/um/UMConfig.php";
require_once "helpers/um/UMPanel.php";
require_once "helpers/um/panels/InformationPanelBuilder.php";
require_once "helpers/um/panels/RulesPanelBuilder.php";
require_once "helpers/um/panels/SchedulePanelBuilder.php";
require_once "helpers/um/SubMenuBuilder.php";
registerPlugin('ml_howto', 98, 1.0);

function generatePointsArray() {
    $first = 500;
    $last = 10;
    $spots = 24;

    $targetSecondPct = 0.77;
    $targetSecond = $first * $targetSecondPct;

// rank 2 => t = 1/($spots-1)
    $t2 = 1 / ($spots - 1);

// Solve for $power:
// (targetSecond - first) / (last - first) = t2^power
    $ratio = ($targetSecond - $first) / ($last - $first);
    $power = log($ratio) / log($t2); // ~0.510...

    $umConfig['um4_semi'] = array();
    for ($rank = 1; $rank <= $spots; $rank++) {
        $t = ($rank - 1) / ($spots - 1);
        $umConfig['um4_semi'][$rank] = (int)round($first + ($last - $first) * pow($t, $power));
        $umConfig['um4_semi'][$rank] = round($umConfig['um4_semi'][$rank] / 10) * 2;
    }

    console(print_r($umConfig['um4_semi'], true));

// quick sanity check
    echo "power=" . $power . "\n";
    echo "1st=" . $umConfig['um4_semi'][1] . "\n";
    echo "2nd=" . $umConfig['um4_semi'][2] . " (" . round($umConfig['um4_semi'][2] / $umConfig['um4_semi'][1] * 100, 2) . "%)\n";
    echo "24th=" . $umConfig['um4_semi'][24] . "\n";
}

//--------------------------------------------------------------
// Init : (plugin init)
//--------------------------------------------------------------
function ml_howtoInit($event) {
    global $_mldebug, $_ml_howto_force, $_ml_act, $umScoreBoardPlayerActions, $umScoreBoardSelectedPlayerRow, $umConfig, $umState, $umScoreBoardPlayers, $selectedPlayer;
    $umState = new UMState();
    $umConfig = new UMConfig();
    $umScoreBoardSelectedPlayerRow = array();
    // here we store the player's race results'
    $selectedPlayer = array();
    $_ml_howto_force = false;

    // will set a unique action='' value in $_ml_act['ml_howto.quit']
    // see function description in manialinks plugin
    manialinksAddAction('ml_howto.quit');
    manialinksAddAction('ml_howto.force');

    // get a unique manialink id. Use the same name as for
    // manialinksAddManialink(). It will add its value automatically in <manialink id='xx'>
    manialinksAddId('ml_howto');

    $umScoreBoardPlayerActions = array();
    for ($i = 0; $i < 16; $i++) {
        $actionName = 'umScoreBoardPlayerActions.' . $i;
        manialinksAddAction($actionName);          // creates a unique action id
        $umScoreBoardPlayerActions[$i] = $_ml_act[$actionName]; // numeric action id stored by manialinks plugin
    }

    $umScoreBoardPlayers = MatchlogFileParser::getScoreboardPlayersFromMatchlog('fastlog/um3_semi.txt', $umConfig->um3Semi->pointsDistribution);
    generatePointsArray();

    // player races pagination
    manialinksAddAction('races.prev');
    manialinksAddAction('races.next');
    // Tabs (Players / Stints)
    manialinksAddAction('um.tab.players');
    manialinksAddAction('um.tab.stints');
    manialinksAddAction('um.tab.schedule');
    manialinksAddAction('um.tab.rules');
    manialinksAddAction('um.tab.information');

    // Submenus (right-side inside rules-panel)
    manialinksAddAction('um.subtab.rules.qualification');
    manialinksAddAction('um.subtab.rules.qualification-points');
    manialinksAddAction('um.subtab.rules.semi-final');
    manialinksAddAction('um.subtab.rules.misc');
}


//--------------------------------------------------------------
// PlayerConnect : (event from server callback)
//--------------------------------------------------------------
function ml_howtoPlayerConnect($event, $login) {
    global $_players, $umState, $umConfig, $umScoreBoardPlayers, $umScoreBoardSelectedPlayerRow;

    if ($umState->shouldUpdateXml) {
        ml_howtoUpdateXml($login, 'show');
        $umState->shouldUpdateXml = false;
    }

    // select player in the scoreboard.
    // TODO: if player is not in board, select first player.
    for ($i = 0; $i < count($umScoreBoardPlayers); $i++) {
        console("umScoreBoardPlayers[$i]");
        if ($umScoreBoardPlayers[$i]['Login'] == $login) {
            $umScoreBoardSelectedPlayerRow[$login] = $i;
            $selectedPlayer[$login] = &$umScoreBoardPlayers[$i];
        }
        //console(print_r($umScoreBoardPlayers[$i], true));
    }

    //if (!)

    if (!isset($_players[$login]['ML']['player.races.page'])) {
        $_players[$login]['ML']['player.races.page'] = 0; // 0-based page index
    }

    // Default tab
    if (!isset($_players[$login]['ML']['um.tab']) || $_players[$login]['ML']['um.tab'] === '') {
        $_players[$login]['ML']['um.tab'] = 'players';
    }

    ml_howtoUpdateXml($login, 'show');
}


//--------------------------------------------------------------
// PlayerShowML : (event from manialink plugin when the player set it on/off)
//--------------------------------------------------------------
function ml_howtoPlayerShowML($event, $login, $ShowML) {
    global $_mldebug;
    if ($_mldebug > 0) console("ml_howto.Event[$event]('$login',$ShowML)");

    if ($ShowML > 0)
        ml_howtoUpdateXml($login, 'show');
}


//--------------------------------------------------------------
// PlayerManialinkPageAnswer : (event from server callback)
//--------------------------------------------------------------
function ml_howtoPlayerManialinkPageAnswer($event, $login, $answer, $action) {
    global $_mldebug, $_ml_howto_force, $umScoreBoardSelectedPlayerRow, $selectedPlayer, $_players;
    console("ml_howto.Event[$event]('$login',$answer,$action)");

    // Tabs
    if (strpos($action, 'um.tab.') === 0) {
        $parts = explode('.', $action);
        $_players[$login]['ML']['um.tab'] = isset($parts[2]) ? $parts[2] : 'players';
        ml_howtoUpdateXml($login, 'show');
        return;
    }

    // Subtabs (submenu on the right, inside the panel)
    if (strpos($action, 'um.subtab.') === 0) {
        // Expected: um.subtab.<tab>.<subkey>
        $parts = explode('.', $action);
        $tabKey = isset($parts[2]) ? $parts[2] : '';
        $subKey = isset($parts[3]) ? $parts[3] : '';
        if ($tabKey !== '' && $subKey !== '') {
            $_players[$login]['ML']['um.subtab.' . $tabKey] = $subKey;
            ml_howtoUpdateXml($login, 'show');
        }
        return;
    }

    if ($action === 'races.prev' || $action === 'races.next') {
        if (!isset($_players[$login]['ML']['player.races.page'])) {
            $_players[$login]['ML']['player.races.page'] = 0;
        }

        if (!isset($selectedPlayer[$login])) {
            return;
        }

        $pageCount = UMPanel::racesPageCount($selectedPlayer[$login]['Races']);

        $page = (int)$_players[$login]['ML']['player.races.page'];
        if ($action === 'races.prev') $page--;
        if ($action === 'races.next') $page++;

        $page = UMPanel::clampInt($page, 0, $pageCount - 1);

        $_players[$login]['ML']['player.races.page'] = $page;

        ml_howtoUpdateXml($login, 'show');

        return;
    }

    $parts = explode('.', $action);
    $playerRow = isset($parts[1]) ? $parts[1] : -1;
    $umScoreBoardSelectedPlayerRow[$login] = $playerRow;

    ml_howtoUpdateXml($login, 'show');
}

//--------------------------------------------------------------
// Function called to handle the manialink drawing
// action can be 'show', 'refresh', 'hide', 'remove'
//--------------------------------------------------------------
function ml_howtoUpdateXml($login, $action = 'show') {

    global $_mldebug, $_ml_act, $_players, $_ml_howto_force;
    // if the players disabled manialinks then do nothing
    if (!isset($_players[$login]['ML']['ShowML']) || $_players[$login]['ML']['ShowML'] <= 0)
        return;

    $xml = getUMPanelXml($login);
    manialinksSet($login, 'ml_howto', $action, $xml);
}

function getUMPanelXml($login) {
    global $umScoreBoardPlayerActions, $umScoreBoardSelectedPlayerRow, $umScoreBoardPlayers, $selectedPlayer;

    $layout = umUiBuildLayout();

    $players = $umScoreBoardPlayers;
    $selectedRow = isset($umScoreBoardSelectedPlayerRow[$login]) ? (int)$umScoreBoardSelectedPlayerRow[$login] : -1;

    $selectedPlayer = array(); // keep per-login selection
    $frameStart = '<frame posn="0 0 5">';

    $bordersXml = umUiBuildPanelBordersXml($layout);

    $listBuild = umUiBuildPlayerListXml($login, $layout, $players, $selectedRow, $umScoreBoardPlayerActions);
    $xmlPlayers = $listBuild['xmlPlayers'];
    if ($listBuild['selectedPlayerRef'] !== null) {
        $selectedPlayer[$login] = &$listBuild['selectedPlayerRef'];
    }

    $rightBuild = umUiBuildRightPanelXml($login, $layout, $selectedPlayer, $layout['panelBgColor']);
    $rightPanelXml =
        $rightBuild['panelFrameStart']
        . $rightBuild['tabsXml']
        . $rightBuild['panelBgQuad']
        . $rightBuild['panelTitle']
        . $rightBuild['panelBody']
        . $rightBuild['panelFrameEnd'];

    $xmlUI =
        $frameStart
        . $bordersXml
        . $layout['playerFrameStart']
        . $xmlPlayers
        . $layout['playerFrameEnd']
        . $rightPanelXml
        . $layout['frameEnd'];

    return $xmlUI;
}

// --- Helpers ----------------------------------------------------

function umUiBuildLayout() {
    // Central place for "magic numbers" + derived geometry.
    $bgW = 90.0;
    $bgH = 60.0;
    $rows = 16;

    $playerW = $bgW * 0.30;
    $playerH = $bgH / ($rows + 1);

    $bgTopLeftX = -$bgW / 2.0;
    $bgTopLeftY = $bgH / 2.0;
    $listFrameY = $bgTopLeftY - ($playerH / 2.0);

    $borderThickness = 0.1;
    $borderColor = '010D';

    $borderLeftX = $bgTopLeftX - $borderThickness;
    $borderHeight = $bgH - $playerH;
    $borderTopY = $listFrameY + $borderThickness;
    $borderTopWidth = $bgW + $borderThickness * 2.0;
    $borderBottomY = $listFrameY - $borderHeight;
    $borderRightX = $borderLeftX + $borderTopWidth - $borderThickness;

    $gap = 0.0;
    $panelW = $bgW - $playerW - $gap;
    if ($panelW < 0) $panelW = 0;
    $panelH = $bgH - $playerH;

    $panelX = $playerW + $gap;

    $panelBodyTopY = -5.0;
    $panelBodyHeight = ($panelH - 6.0);

    return array(
        'bgW' => $bgW,
        'bgH' => $bgH,
        'rows' => $rows,
        'playerW' => $playerW,
        'playerH' => $playerH,
        'bgTopLeftX' => $bgTopLeftX,
        'listFrameY' => $listFrameY,

        'borderThickness' => $borderThickness,
        'borderColor' => $borderColor,
        'borderLeftX' => $borderLeftX,
        'borderHeight' => $borderHeight,
        'borderTopY' => $borderTopY,
        'borderTopWidth' => $borderTopWidth,
        'borderBottomY' => $borderBottomY,
        'borderRightX' => $borderRightX,

        'panelW' => $panelW,
        'panelH' => $panelH,
        'panelFrameStart' => "<frame posn='" . ($bgTopLeftX + $panelX) . " {$listFrameY} 0.1'>",
        'panelFrameEnd' => "</frame>",

        'panelBodyTopY' => $panelBodyTopY,
        'panelBodyHeight' => $panelBodyHeight,

        'playerFrameStart' => "<frame posn='{$bgTopLeftX} {$listFrameY} 0.1'>",
        'playerFrameEnd' => "</frame>",

        'panelBgColor' => '010D', // your current selected panel background
        'bgColorCard' => '020D',
        'bgColorCardSelected' => '010D',
        'bgTab' => '010D',
        'bgTabActive' => '060D',
        'headerFont' => '$cf0$o',
        'frameEnd' => '</frame>',

        // text
        'greenTextColor' => '$390'
    );
}

function umUiBuildPanelBordersXml($layout) {
    $t = $layout['borderThickness'];
    $c = $layout['borderColor'];

    $left = "<quad posn='{$layout['borderLeftX']} {$layout['listFrameY']} 0' sizen='{$t} {$layout['borderHeight']}' halign='left' valign='top' bgcolor='{$c}'/>";
    $top = "<quad posn='{$layout['borderLeftX']} {$layout['borderTopY']} 0' sizen='{$layout['borderTopWidth']} {$t}' halign='left' valign='top' bgcolor='{$c}'/>";
    $bottom = "<quad posn='{$layout['borderLeftX']} {$layout['borderBottomY']} 0' sizen='{$layout['borderTopWidth']} {$t}' halign='left' valign='top' bgcolor='{$c}'/>";
    $right = "<quad posn='{$layout['borderRightX']} {$layout['listFrameY']} 0' sizen='{$t} {$layout['borderHeight']}' halign='left' valign='top' bgcolor='{$c}'/>";

    return $left . $top . $bottom . $right;
}

function umUiBuildPlayerListXml($login, $layout, $players, $selectedRow, $actionIds) {
    $padX = 1.0;
    $padY = 1.5;

    $rows = (int)$layout['rows'];
    $playerW = $layout['playerW'];
    $playerH = $layout['playerH'];

    $pointsW = 6.0;
    $pointsRightX = $playerW - $padX;

    $rowSpacing = 0.0;

    $xmlPlayers = '';
    $selectedPlayerRef = null;

    for ($i = 0; $i < $rows; $i++) {
        $rowY = -$i * ($playerH + $rowSpacing);

        $name = isset($players[$i]) ? $players[$i]['NickNameWithColor'] : '';
        $points = isset($players[$i]) ? $players[$i]['Points'] : '';
        $bg = ($i === $selectedRow) ? $layout['bgColorCardSelected'] : $layout['bgColorCard'];

        $actionId = isset($actionIds[$i]) ? (int)$actionIds[$i] : 0;
        if ($i === $selectedRow && isset($players[$i])) {
            $selectedPlayerRef = &$players[$i];
        }

        $xmlPlayers .= "<quad posn='0 {$rowY} 0' sizen='{$playerW} {$playerH}' halign='left' valign='top' bgcolor='{$bg}' action='{$actionId}'/>";
        $xmlPlayers .= "<label posn='{$padX} " . ($rowY - $padY) . " 0.2' sizen='" . ($playerW - 1.2) . " {$playerH}' halign='left' valign='center' textsize='1' text='\$fc0" . ($i + 1) . "'/>";

        $nameLeftX = $padX * 3.0;
        $nameW = ($pointsRightX - $pointsW) - $nameLeftX;

        $xmlPlayers .= "<label posn='{$nameLeftX} " . ($rowY - $padY) . " 0.2' sizen='{$nameW} {$playerH}' halign='left' valign='center' textsize='1' text='" . safeString($name) . "'/>";
        $xmlPlayers .= "<label posn='{$pointsRightX} " . ($rowY - $padY) . " 0.3' sizen='{$pointsW} {$playerH}' halign='right' valign='center' textsize='1' text='" . safeString($points) . "'/>";
    }

    return array(
        'xmlPlayers' => $xmlPlayers,
        'selectedPlayerRef' => $selectedPlayerRef,
    );
}

function umUiBuildRightPanelXml($login, $layout, $selectedPlayer, $panelBgColor) {
    global $_players;

    $panelW = $layout['panelW'];
    $panelH = $layout['panelH'];

    $tabsXml = umUiBuildTabsXml($login, $layout);

    $panelBgQuad = "<quad posn='0 0 0' sizen='{$panelW} {$panelH}' halign='left' valign='top' bgcolor='{$panelBgColor}'/>";

    $activeTab = (isset($_players[$login]['ML']['um.tab']) && $_players[$login]['ML']['um.tab'] !== '')
        ? $_players[$login]['ML']['um.tab']
        : 'players';

    // Title can also depend on the tab
    $selectedPlayerName = isset($selectedPlayer[$login]) ? $selectedPlayer[$login]['NickNameWithColor'] : '';
    $titleText = ($activeTab === 'players') ? $selectedPlayerName : ucwords($activeTab);
    $font = ($activeTab === 'players') ? '' : $layout['headerFont'];
    $panelTitle = "<label posn='1 -1 0.2' sizen='" . ($panelW - 2) . " 3' halign='left' valign='top' textsize='2' text='{$font}" . safeString($titleText) . "'/>";

    $panelBodyTopY = -5.0;
    $panelBodyH = ($panelH - 6.0);
    if ($panelBodyH < 0) $panelBodyH = 0;

    $panelBody = umUiBuildRightPanelBodyXml($login, $activeTab, $selectedPlayer, $layout);

    return array(
        'panelFrameStart' => $layout['panelFrameStart'],
        'panelFrameEnd' => $layout['panelFrameEnd'],
        'tabsXml' => $tabsXml,
        'panelBgQuad' => $panelBgQuad,
        'panelTitle' => $panelTitle,
        'panelBody' => $panelBody,
    );
}

function umUiBuildRightPanelBodyXml($login, $activeTab, $selectedPlayer, $layout) {
    global $umConfig;

    switch ($activeTab) {
        case 'schedule':
            return SchedulePanelBuilder::schedule($layout, $umConfig);
        case 'rules':
            return RulesPanelBuilder::build($login, $layout, $umConfig);
        case 'information':
            return InformationPanelBuilder::getInformationPanel($layout);

        case 'stints':
            return umUiBuildStintsPanelXml($login, $layout);

        case 'players':
        default:
            return umUiBuildPlayerRacesPanelXml($login, $selectedPlayer, $layout);
    }
}

function umUiBuildPlayerRacesPanelXml($login, $selectedPlayer, $layout) {
    if (!isset($selectedPlayer[$login]) || !isset($selectedPlayer[$login]['Races']) || !is_array($selectedPlayer[$login]['Races']) || count($selectedPlayer[$login]['Races']) < 1) {
        return UMPanel::textLabel($layout, 'Select a player on the left...');
    }

    return buildRacesTableXml($login, $selectedPlayer[$login]['Races'], $layout);
}


function umUiBuildStintsPanelXml($login, $layout) {
    // Placeholder until you implement real stints UI
    return "";
}


function umUiBuildTabsXml($login, $layout) {
    global $_players, $_ml_act;

    $tabH = 3;
    $tabGap = 0.0;
    $tabRightMargin = 1.2;

    $tabTextPrefix = '$fff$o';
    $tabLift = 0.5;
    $tabTextY = -($tabH / 1.5) + $tabLift;

    // Define tabs in one place
    $tabs = array(
        array('key' => 'players', 'title' => 'Players', 'action' => 'um.tab.players'),
        array('key' => 'stints', 'title' => 'Stints', 'action' => 'um.tab.stints'),
        // Future:
        array('key' => 'prize', 'title' => 'Prize Pool', 'action' => 'um.tab.prize'),
        array('key' => 'schedule', 'title' => 'Schedule', 'action' => 'um.tab.schedule'),
        array('key' => 'rules', 'title' => 'Rules', 'action' => 'um.tab.rules'),
        array('key' => 'information', 'title' => 'Information', 'action' => 'um.tab.information'),
    );

    // Active tab from per-player UI state
    $activeTab = (isset($_players[$login]['ML']['um.tab']) && $_players[$login]['ML']['um.tab'] !== '')
        ? $_players[$login]['ML']['um.tab']
        : $tabs[0]['key'];

    // Precompute widths + total width
    $totalW = 0.0;
    $count = count($tabs);

    for ($i = 0; $i < $count; $i++) {
        $tabs[$i]['w'] = UMPanel::mlTabWidth($tabs[$i]['title'], 1.0, 1.8, 6.0, 26.0);
        $totalW += $tabs[$i]['w'];
        if ($i > 0) $totalW += $tabGap;
    }

    // Right-align group inside panel
    $tabsX = $layout['panelW'] - $tabRightMargin - $totalW;
    if ($tabsX < 0) $tabsX = 0;

    // y = $tabH makes the quad extend down to y=0 (panel top edge), so it looks "attached"
    $tabsY = $tabH;

    $xml = "<frame posn='{$tabsX} {$tabsY} 0.30' halign='left' valign='top'>";

    // --- Tabs menu border + dividers (because gap is 0) ---
    $borderT = 0.12;              // thickness
    $borderColor = 'FFFFFF66';    // ARGB-ish manialink color string (tweak to your taste)
    $dividerT = 0.10;

    // Top border across the whole tab group (at y=0, since quads extend down)
    $xml .= "<quad posn='0 0 0.02' sizen='{$totalW} {$borderT}' halign='left' valign='top' bgcolor='{$layout['borderColor']}'/>";

    // Left border
    $xml .= "<quad posn='0 0 0.02' sizen='{$borderT} {$tabH}' halign='left' valign='top' bgcolor='{$layout['borderColor']}'/>";

    // Right border
    $rightX = $totalW - $borderT;
    if ($rightX < 0) $rightX = 0;
    $xml .= "<quad posn='{$rightX} 0 0.02' sizen='{$borderT} {$tabH}' halign='left' valign='top' bgcolor='{$layout['borderColor']}'/>";

    // Build tabs left-to-right
    $x = 0.0;
    for ($i = 0; $i < $count; $i++) {
        $w = $tabs[$i]['w'];
        $isActive = ($activeTab === $tabs[$i]['key']);
        $bg = $isActive ? $layout['bgTabActive'] : $layout['bgTab'];

        $actName = $tabs[$i]['action'];
        $actId = isset($_ml_act[$actName]) ? (int)$_ml_act[$actName] : 0;

        // Clickable background
        $xml .= "<quad posn='{$x} 0 0' sizen='{$w} {$tabH}' halign='left' valign='top' bgcolor='{$bg}' action='{$actId}'/>";

        // Divider between tabs (skip after last tab)
        if ($i < ($count - 1)) {
            $divX = $x + $w - ($dividerT / 2.0); // centers divider on the boundary
            $xml .= "<quad posn='{$divX} 0 0.015' sizen='{$dividerT} {$tabH}' halign='left' valign='top' bgcolor='{$layout['borderColor']}'/>";
        }

        // Centered label
        $centerX = $x + ($w / 2.0);
        $xml .= "<label posn='{$centerX} {$tabTextY} 0.1' sizen='{$w} {$tabH}' halign='center' valign='center' textsize='1' text='{$tabTextPrefix}{$tabs[$i]['title']}'/>";

        $x += $w + $tabGap;
    }

    $xml .= "</frame>";
    return $xml;
}

function buildRacesTableXml($login, $races, $layout) {
    global $playerRaces, $_players, $_ml_act;
    // Column layout: 5 columns (Idx | Env | Rank | Pts | Time)
    // Use ONE shared content margin so backgrounds + columns always align (and match panelTitle visually).
    $panelW = $layout['panelW'];
    $panelH = $layout['panelH'];
    $topY = $layout['panelBodyTopY'];

    $contentL = 1.2;
    $contentR = 1.2;

    $gutter = 0.5;

    // Add a slightly bigger gap specifically between "#" and "Environment"
    $gutterAfterIdx = 0.9;

    // Make the first column (race index) a bit wider
    $idxW = 3.0;

    // Usable width must account for 4 gaps total, but one of them is special now
    $usableW = $panelW - $contentL - $contentR - (3 * $gutter) - $gutterAfterIdx;
    if ($usableW < 0) $usableW = 0;

    if ($idxW > $usableW) $idxW = $usableW;

    $otherW = $usableW - $idxW;
    if ($otherW < 0) $otherW = 0;
    $colW = $otherW / 4.0;

    // Column X positions
    // RaceIndex is now LEFT-aligned, so X should be the LEFT edge of the idx column.
    $idxPadL = 0.4; // tweak: 0.3–0.6 usually looks nice
    $timePadR = $idxPadL;
    $xIdxLeft = $contentL + $idxPadL;
    $xEnv = $contentL + $idxW + $gutterAfterIdx;
    $xRank = $xEnv + $colW + $gutter;
    $xPts = $xRank + $colW + $gutter;

    // Time aligned to the RIGHT content edge (same margin logic as left)
    $xTimeRight = $panelW - $contentR - $timePadR;

    // Backgrounds should cover the full table area INCLUDING the raceIndex column
    $tableX = $contentL;
    $tableW = $panelW - $contentL - $contentR;
    if ($tableW < 0) $tableW = 0;

    $rowH = 2.4;
    $headerY = $topY;

    // How many rows fit (minus 1 for header)
    $maxRows = 10;
    if ($panelH > ($rowH * 2)) {
        $maxRows = (int)floor(($panelH / $rowH) - 1);
    }
    if ($maxRows < 1) $maxRows = 1;

    $page = isset($_players[$login]['ML']['player.races.page']) ? (int)$_players[$login]['ML']['player.races.page'] : 0;

    $pageCount = UMPanel::racesPageCount($races);
    $page = UMPanel::clampInt($page, 0, $pageCount - 1);
    $_players[$login]['ML']['player.races.page'] = $page;

    // Show newest races first
    $racesToShow = is_array($races) ? UMPanel::racesSliceForPage($races, $page) : array();

    $xml = '';
    $headerFont = '$cf0$o';

    // Header background (subtle) - now guaranteed to cover the # header too
    $xml .= "<quad posn='{$tableX} {$headerY} 0.15' sizen='{$tableW} {$rowH}' halign='left' valign='top' bgcolor='0006'/>";

    // Header labels (Idx | Env | Rank | Pts | Time)
    $xml .= "<label posn='{$xIdxLeft} " . ($headerY - 0.6) . " 0.2' sizen='" . ($idxW - $idxPadL) . " {$rowH}' halign='left' valign='top' textsize='1' text='{$headerFont}#'/>";
    $xml .= "<label posn='{$xEnv} " . ($headerY - 0.6) . " 0.2' sizen='{$colW} {$rowH}' halign='left'  valign='top' textsize='1' text='{$headerFont}Environment'/>";
    $xml .= "<label posn='{$xRank} " . ($headerY - 0.6) . " 0.2' sizen='{$colW} {$rowH}' halign='right' valign='top' textsize='1' text='{$headerFont}Rank'/>";
    $xml .= "<label posn='{$xPts} " . ($headerY - 0.6) . " 0.2' sizen='{$colW} {$rowH}' halign='right' valign='top' textsize='1' text='{$headerFont}Points'/>";
    $xml .= "<label posn='{$xTimeRight} " . ($headerY - 0.6) . " 0.2' sizen='" . ($colW - $timePadR) . " {$rowH}' halign='right' valign='top' textsize='1' text='{$headerFont}Time'/>";

    // Rows
    $i = 0;
    foreach ($racesToShow as $race) {
        $rowY = $headerY - (($i + 1) * $rowH);

        if (isset($race['RaceIndex'])) {
            $raceIdx = (string)(((int)$race['RaceIndex']) + 1);
        } else {
            $raceIdx = (string)($i + 1);
        }

        $env = '';
        if (isset($race['RaceInfo']) && is_array($race['RaceInfo']) && isset($race['RaceInfo']['Environment'])) {
            $env = $race['RaceInfo']['Environment'];
        }

        $rank = isset($race['Rank']) ? (string)$race['Rank'] : '';

        $time = '';
        if (isset($race['Score']) && is_array($race['Score'])) {
            if (isset($race['Score']['Time']) && $race['Score']['Time'] !== '') {
                $time = $race['Score']['Time'];
            } elseif (isset($race['Score']['RaceTime']) && $race['Score']['RaceTime'] !== '') {
                $time = $race['Score']['RaceTime'];
            }
        }

        $pts = isset($race['AwardedPoints']) ? (string)$race['AwardedPoints'] : '';

        $enviFont = '$390$o';
        $otherFont = $rank > 3 ? '$fff$o' : '$fc0$o';

        // Zebra background
        $bg = (($i % 2) === 0) ? '0003' : '0000';
        $xml .= "<quad posn='{$tableX} {$rowY} 0.10' sizen='{$tableW} {$rowH}' halign='left' valign='top' bgcolor='{$bg}'/>";

        // Row labels
        $xml .= "<label posn='{$xIdxLeft} " . ($rowY - 0.6) . " 0.2' sizen='" . ($idxW - $idxPadL) . " {$rowH}' halign='left' valign='top' textsize='1' text='{$otherFont}" . safeString($raceIdx) . "'/>";
        $xml .= "<label posn='{$xEnv} " . ($rowY - 0.6) . " 0.2' sizen='{$colW} {$rowH}' halign='left'  valign='top' textsize='1' text='{$enviFont}" . safeString($env) . "'/>";
        $xml .= "<label posn='{$xRank} " . ($rowY - 0.6) . " 0.2' sizen='{$colW} {$rowH}' halign='right' valign='top' textsize='1' text='{$otherFont}" . safeString($rank) . "'/>";
        $xml .= "<label posn='{$xPts} " . ($rowY - 0.6) . " 0.2' sizen='{$colW} {$rowH}'  halign='right' valign='top' textsize='1' text='{$otherFont}" . safeString($pts) . "'/>";
        $xml .= "<label posn='{$xTimeRight} " . ($rowY - 0.6) . " 0.2' sizen='" . ($colW - $timePadR) . " {$rowH}' halign='right' valign='top' textsize='1' text='{$otherFont}" . safeString($time) . "'/>";
        $i++;

    }

    if (is_array($races) && count($races) > RACES_PER_PAGE) {
        $pageCount = UMPanel::racesPageCount($races);
        $page = (int)$_players[$login]['ML']['player.races.page'];

        $canPrev = ($page > 0);
        $canNext = ($page < $pageCount - 1);

// Move it a bit further DOWN to "breathe"
        $pagerY = $headerY - (($i + 1) * $rowH) - 1.2;

        // Align the RIGHT EDGE of the Next button to the table's right border:
        // Put the frame at the table's LEFT, then position the Next quad so its right edge sits at $tableW.
        $labelW = 2.0; // "1/2"
        $gap = 0.25;

        $nextX = $tableW - 1.6; // quad is 1.6 wide -> right edge = $tableW
        $prevX = $nextX - 1.6 - $gap - $labelW - $gap - 1.6;

        // Center the label DEAD CENTER between the arrows (horizontally)
        $prevCenterX = $prevX + (1.6 / 2.0);
        $nextCenterX = $nextX + (1.6 / 2.0);
        $midX = ($prevCenterX + $nextCenterX) / 2.0;

        // Also center it vertically inside the 1.6-high arrow strip:
        // quads are at y=0 (top) with height 1.6 => vertical center is y = -0.8
        $midY = -0.8;

        $xml .= "<frame posn='{$tableX} {$pagerY} 0.2' halign='left' valign='top'>"
            . "<quad sizen='1.6 1.6' posn='{$prevX} 0 0' style='Icons64x64_1' substyle='ArrowPrev'"
            . ($canPrev ? " action='{$_ml_act['races.prev']}'" : "")
            . "/>"
            . "<label sizen='{$labelW} 1.6' posn='{$midX} {$midY} 0' textsize='1' valign='center' halign='center'"
            . " text='\$aaa" . ($page + 1) . "/" . $pageCount . "'/>"
            . "<quad sizen='1.6 1.6' posn='{$nextX} 0 0' style='Icons64x64_1' substyle='ArrowNext'"
            . ($canNext ? " action='{$_ml_act['races.next']}'" : "")
            . "/>"
            . "</frame>";
    }

    return $xml;
}

function safeString($str) {
    $str = (string)$str;
    // Prevent attribute/newline weirdness
    $str = str_replace(array("\r", "\n", "\t"), ' ', $str);
    // Escape for XML attribute context (handles both ' and ")
    return htmlspecialchars($str, ENT_QUOTES, 'UTF-8');
}


//--------------------------------------------------------------
// PlayerMenuBuild : (event from ml_menu plugin)
//--------------------------------------------------------------
function ml_howtoPlayerMenuBuild($event, $login) {
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
function ml_howtoPlayerMenuAction($event, $login, $action, $state) {
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

function ml_howtoEndRace($event, $Ranking, $ChallengeInfo, $GameInfos, $continuecup, $warmup, $fwarmup) {
    ml_howtoUpdateXml("blackcat111", 'show');
}

/**
 * Returns an array of strings ready for the scoreboard UI.
 * Current format: "Rank. NickName (Points)" (falls back nicely if data is missing)
 */


?>
