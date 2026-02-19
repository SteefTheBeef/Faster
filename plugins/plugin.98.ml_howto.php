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
registerPlugin('ml_howto',98,1.0);

function generatePointsArray() {
    $first = 1200;
    $last  = 10;
    $spots = 24;

    $targetSecondPct = 0.80;
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
        $umConfig['um4_semi'][$rank] = (int) round($first + ($last - $first) * pow($t, $power));
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
function ml_howtoInit($event){
	global $_mldebug,$_ml_howto_force, $_ml_act, $umScoreBoardPlayerActions, $umScoreBoardSelectedPlayerRow, $umConfig, $umState, $umScoreBoardPlayers;
	$umState = new UMState();
	if($_mldebug>0) console("ml_howto.Event[$event]");

	$_ml_howto_force = false;
	$umScoreBoardSelectedPlayerRow = array();

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

    $umConfig['um4_semi_points'] = array(
		1 => 1200,
		2 => 960,
		3 => 858,
		4 => 779,
		5 => 713,
		6 => 654,
		7 => 601,
		8 => 552,
		9 => 506,
		10 => 463,
		11 => 422,
		12 => 383,
		13 => 346,
		14 => 311,
		15 => 276,
		16 => 243,
		17 => 211,
		18 => 180,
		19 => 150,
		20 => 121,
		21 => 92,
		22 => 64,
		23 => 37,
		24 => 10,
    );

	$umConfig['um4_gf_points'] = array(
		1 => 1200,
		2 => 960,
		3 => 858,
		4 => 779,
		5 => 713,
		6 => 654,
		7 => 601,
		8 => 552,
		9 => 506,
		10 => 463,
		11 => 422,
		12 => 383,
		13 => 346,
		14 => 311,
		15 => 276,
		16 => 243,
    );
}


//--------------------------------------------------------------
// PlayerConnect : (event from server callback)
//--------------------------------------------------------------
function ml_howtoPlayerConnect($event,$login){
	global $_mldebug, $umState;
	// if($_mldebug>0) console("ml_howto.Event[$event]('$login')");

	if($umState->shouldUpdateXml) {
		ml_howtoUpdateXml($login, 'show');
		$umState->shouldUpdateXml = false;

	}


	ml_howtoUpdateXml($login, 'show');
}


//--------------------------------------------------------------
// PlayerShowML : (event from manialink plugin when the player set it on/off)
//--------------------------------------------------------------
function ml_howtoPlayerShowML($event,$login,$ShowML){
	global $_mldebug;
	if($_mldebug>0) console("ml_howto.Event[$event]('$login',$ShowML)");

	if($ShowML>0)
		ml_howtoUpdateXml($login,'show');
}


//--------------------------------------------------------------
// PlayerManialinkPageAnswer : (event from server callback)
//--------------------------------------------------------------
function ml_howtoPlayerManialinkPageAnswer($event,$login,$answer,$action){
	global $_mldebug,$_ml_howto_force, $umScoreBoardSelectedPlayerRow;
    console("ml_howto.Event[$event]('$login',$answer,$action)");

    $playerRow = explode('.', $action)[1];
    console("playerRow=$playerRow");

    $umScoreBoardSelectedPlayerRow[$login] = $playerRow;

	ml_howtoUpdateXml($login,'show');

}


//--------------------------------------------------------------
// Function called to handle the manialink drawing
// action can be 'show', 'refresh', 'hide', 'remove'
//--------------------------------------------------------------
function ml_howtoUpdateXml($login,$action='show'){
	global $_mldebug,$_ml_act,$_players,$_ml_howto_force;
	// if the players disabled manialinks then do nothing
	if(!isset($_players[$login]['ML']['ShowML']) || $_players[$login]['ML']['ShowML']<=0)
		return;
	if($_mldebug>0) console("ml_howtoUpdateXml('$login',$action)");

	if($action=='remove'){
		// remove manialink
		manialinksRemove($login,'ml_howto');
		return;

	}elseif($action=='hide'){
		// hide manialink
		manialinksHide($login,'ml_howto');
		return;

	}elseif($action=='refresh' && !manialinksIsOpened($login,'ml_howto')){
		// refresh but not opened: do nothing
		return;
	}
	$bgColor = '0015';
	$bgColorPlayerCard = '0016';

	$frameStart = '<frame posn="0 0 5">';
	$bgQuad = "<quad posn='0 0 0' sizen='90 60' halign='center' valign='center' bgcolor='$bgColor'/>";
	$playerQuad = "<quad posn='0 0 0.1' sizen='30 1' bgcolor='$bgColorPlayerCard'/>";
	$playerLabel = '<label posn="0 0 0.2" textsize="1" text="[TnT]BlackCat12345678912345679"/>';
	$frameEnd = '</frame>';

	// show/refresh
	$xml = sprintf('<frame posn="0 0 5">'
								 .'<label posn="0 0 0.1" textsize="1" text="[TnT]BlackCat12345678912345679"/>'
								 .'<label posn="0 -2  0.4" textsize="1" text="[CMC]Roa"/>'
								 .'<label posn="-2 -9 0.1" halign="right" style="CardButtonMedium" action="%d" text="%s"/>'
								 .'<label posn="2 -9 0.1" halign="left" style="CardButtonMedium" action="%d" text="Quit"/>'
								 .'</frame>',
								 $_ml_act['ml_howto.force'],
								 ($_ml_howto_force ? 'Unforce' : 'Force'),
								 $_ml_act['ml_howto.quit']);


    $ui = renderGraphicsManyPlayers($login);
	// show manialink
	if($_ml_howto_force)
		manialinksShowForce($login,'ml_howto',$ui);
	else
		manialinksShow($login,'ml_howto',$ui);
}

function renderGraphicsManyPlayers($login) {
    global $umScoreBoardPlayerActions, $umScoreBoardSelectedPlayerRow, $umConfig;
    $bgColor = '0015';
    $bgColorPlayerCard = '0016';
    $bgColorPlayerCardSelected = '0af6';

    $bgW = 90.0;
    $bgH = 60.0;

    $rows = 16;

    $playerW = $bgW * 0.30;     // 27
    $playerH = $bgH / ($rows + 1);    // 60/16 = 3.75

    $bgTopLeftX = -$bgW / 2.0;  // -45
    $bgTopLeftY =  $bgH / 2.0;  //  30

	$rowSpacing = 0.0; // set to e.g. 0.1 if you want a small gap between rows
	// Height consumed by the list (rows + spacing between rows)
	$listH = ($rows * $playerH) + (($rows - 1) * $rowSpacing);
	// Space left inside the background
	$freeH = $bgH - $listH;
	// Symmetric padding (if freeH < 0, list doesn't fit; padding becomes negative)
	$padTopBottom = $freeH / 2.0;
	// Anchor list to background top-left, but push it down by the top padding
	$listFrameY = $bgTopLeftY - $padTopBottom;

    $frameStart = '<frame posn="0 0 5">';
    $bgQuad = "<quad posn='0 0 0' sizen='{$bgW} {$bgH}' halign='center' valign='center' bgcolor='{$bgColor}' style='BgsPlayerCard' substyle='BgCardSystem'/>";
    $bgPlayerQuad = "<quad posn='0 0 0' sizen='{$bgW} {$bgH}' halign='center' valign='center' bgcolor='{$bgColorPlayerCard}' style='BgsPlayerCard' substyle='BgCardSystem'/>";
// Player list frame anchored to bg's top-left
	$listFrameY = $bgTopLeftY - ($playerH / 2);
    //$playerFrameStart = "<frame posn='{$bgTopLeftX} {$listFrameY} 0.1'>";

    $padX = 1;
    $padY = 2;

	$pointsW = 6.0;
	$pointsRightX = $playerW - $padX;


	$playerFrameStart = "<frame posn='{$bgTopLeftX} {$listFrameY} 0.1'>";
    // Populate from matchlog file (latest race)
    $players = MatchlogFileParser::getScoreboardPlayersFromMatchlog('fastlog/um3_semi.txt', $umConfig['um4_gf_points']);

    $selectedRow = isset($umScoreBoardSelectedPlayerRow[$login]) ? (int)$umScoreBoardSelectedPlayerRow[$login] : -1;
    $xmlPlayers = '';

	//console(print_r($players[0],true));

    for ($i = 0; $i < $rows; $i++) {
        $rowY = -$i * ($playerH + $rowSpacing);

        $name = isset($players[$i]) ? $players[$i]['NickNameWithColor'] : ''; // blank row if no player
        $points = isset($players[$i]) ? $players[$i]['Points'] : ''; // blank row if no player
        $bgPlayerCard = ($i === $selectedRow) ? $bgColorPlayerCardSelected : $bgColorPlayerCard;
        $actionId = isset($umScoreBoardPlayerActions[$i]) ? (int)$umScoreBoardPlayerActions[$i] : 0;

        $xmlPlayers .= "<quad posn='0 {$rowY} 0' sizen='{$playerW} {$playerH}' halign='left' valign='top' bgcolor='{$bgPlayerCard}' action='{$actionId}'/>";
        $xmlPlayers .= "<label posn='{$padX} " . ($rowY - $padY) . " 0.2' sizen='" . ($playerW - 1.2) . " {$playerH}' halign='left' valign='center' textsize='1' text='\$fc0". ($i + 1) ."'/>";

		// Name column should stop before the points column
		$nameLeftX  = $padX * 3;
		$nameW      = ($pointsRightX - $pointsW) - $nameLeftX;

		$xmlPlayers .= "<label posn='{$nameLeftX} " . ($rowY - $padY) . " 0.2' "
			.  "sizen='{$nameW} {$playerH}' halign='left' valign='center' textsize='1' "
			.  "text='" . safeString($name) . "'/>";

		// Points (right-aligned inside its own column at the right side)
		$xmlPlayers .= "<label posn='{$pointsRightX} " . ($rowY - $padY) . " 0.3' "
			.  "sizen='{$pointsW} {$playerH}' halign='right' valign='center' textsize='1' "
			.  "text='" . safeString($points) . "'/>";
    }

	$playerFrameEnd = '</frame>';
    $frameEnd = '</frame>';

    $xmlUI = $frameStart
        . $bgQuad
		. $bgPlayerQuad
        . $playerFrameStart
        . $xmlPlayers
        . $playerFrameEnd
        . $frameEnd;

    return $xmlUI;
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
function ml_howtoPlayerMenuBuild($event,$login){
	global $_mldebug;
	if($_mldebug>0) console("ml_howto.Event[$event]('$login')");

	// add a new menu 'menu.howto' in 'menu.main' :
	$menuitem = array('Name'=>'Howto ...','Menu'=>array('DefaultStyles'=>true,'Width'=>13,'Items'=>array()));
	$menuitem['Menu']['Items']['menu.howto.item1'] = array('Name'=>'Item1','Type'=>'item');
	$menuitem['Menu']['Items']['menu.howto.hide1'] = array('Name'=>'Hide1','Type'=>'hide');
	$menuitem['Menu']['Items']['menu.howto.bool1'] = array('Name'=>'Bool1','Type'=>'bool');
	$menuitem['Menu']['Items']['menu.howto.bool2'] = array('Name'=>array('Bool2:on','Bool2:off'),'Type'=>'bool','State'=>false);
	$menuitem['Menu']['Items']['menu.howto.multi1'] = array('Name'=>array('Multi:1','Multi:2','Multi:3'),'Type'=>'multi');
	$menuitem['Menu']['Items']['menu.howto.menu1'] = array('Name'=>'Menu1','Menu'=>array('Items'=>array()));
	ml_menusAddItem($login, 'menu.main', 'menu.howto', $menuitem);
	// add an item in submenu 'menu.howto.menu1' :
	ml_menusAddItem($login, 'menu.howto.menu1', 'menu.howto.item2', array('Name'=>'Item2','Type'=>'item'));
	ml_menusAddItem($login, 'menu.howto.menu1', 'menu.howto.hide2', array('Name'=>'Hide2','Type'=>'hide'));

	// add also 'menu.howto.bool1' in Hud :
	ml_menusAddItem($login, 'menu.hud', 'menu.howto.bool1', array('Name'=>'Bool1','Type'=>'bool'));



	// make a new menu elsewhere, at bottom right :
	$menu2 = array('Show'=>true,'X'=>64,'Y'=>-48,
								 'Pos'=>'bottom','SubPos'=>'right',
								 'DefaultStyles'=>true,'Width'=>13,'Items'=>array());
	$menu2['Items']['menu.htmenu.item2'] = array('Name'=>'Item2');
	$menu2['Items']['menu.htmenu.item3'] = array('Name'=>'Item3');
	ml_menusNewMenu($login,'menu.htmenu',$menu2);
	// add a submenu, items with same idname are shared ! submenus can't be shared, only simple items !!
	$menuitem2 = array('Name'=>'Howto2 ...','Menu'=>array('DefaultStyles'=>true,'Width'=>13,'Items'=>array()));
	$menuitem2['Menu']['Items']['menu.howto.item1'] = array('Name'=>'Item1','Type'=>'item');
	$menuitem2['Menu']['Items']['menu.howto.hide1'] = array('Name'=>'Hide1','Type'=>'hide');
	$menuitem2['Menu']['Items']['menu.howto2.hide2'] = array('Name'=>'Hide2','Type'=>'hide');
	$menuitem2['Menu']['Items']['menu.howto.bool1'] = array('Name'=>'Bool1','Type'=>'bool');
	$menuitem2['Menu']['Items']['menu.howto.bool2'] = array('Name'=>array('Bool2:on','Bool2:off'),'Type'=>'bool','State'=>false);
	$menuitem2['Menu']['Items']['menu.howto.multi1'] = array('Name'=>array('Multi:1','Multi:2','Multi:3'),'Type'=>'multi');
	$menuitem2['Menu']['Items']['menu.howto2.menu2'] = array('Name'=>'Menu2','Menu'=>array('Items'=>array()));
	$menuitem2['Menu']['Items']['menu.howto2.menu2']['Menu']['Items']['menu.howto2.menu2.item1'] = array('Name'=>'Item1','Type'=>'item');
	ml_menusAddItem($login, 'menu.htmenu', 'menu.howto2', $menuitem2);
	ml_menusAddItem($login, 'menu.howto2.menu2', 'menu.howto2.menu2.item2', array('Name'=>'Item2','Type'=>'item'));
	// show the new menu
	ml_menusShowMenu($login,'menu.htmenu');
}


//--------------------------------------------------------------
// PlayerMenuAction : (event from ml_menus plugin)
//--------------------------------------------------------------
function ml_howtoPlayerMenuAction($event,$login,$action,$state){
	global $_mldebug;
	if($_mldebug>0) console("ml_howto.Event[$event]('$login',$action,$state)");

	if($action=='menu.howto.item1'){
		addCall(null,'ChatSendToLogin', "HowTo: \$fff$action  $state", $login);
	}elseif($action=='menu.howto.hide1'){
		addCall(null,'ChatSendToLogin', "HowTo: \$fff$action = $state", $login);
	}elseif($action=='menu.howto.bool1'){
		addCall(null,'ChatSendToLogin', "HowTo: \$fff$action = $state", $login);
	}elseif($action=='menu.howto.bool2'){
		addCall(null,'ChatSendToLogin', "HowTo: \$fff$action = $state", $login);
	}elseif($action=='menu.howto.multi1'){
		addCall(null,'ChatSendToLogin', "HowTo: \$fff$action = $state", $login);
	}elseif($action=='menu.howto.menu1'){
		addCall(null,'ChatSendToLogin', "HowTo: \$fff$action = $state", $login);
	}
}

function ml_howtoEndRace($event,$Ranking,$ChallengeInfo,$GameInfos,$continuecup,$warmup,$fwarmup){
	console("ml_howtoEndRace::".print_r($Ranking, true));
	ml_howtoUpdateXml("blackcat111",'show');
}

/**
 * Returns an array of strings ready for the scoreboard UI.
 * Current format: "Rank. NickName (Points)" (falls back nicely if data is missing)
 */





?>
