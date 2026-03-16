<?php


class UmMiniScoreBoardRenderContext {
    public $login;
    public $layout;

    public $selectPlayerActionIds;
    public $mlAct;
    public $umConfig;
    public $umState;
    public $gameInfos;
    public $challengeInfo;

    public function __construct($login, Layout $layout, UMConfig $umConfig, UmState $umState, $mlAct) {
        global $_GameInfos, $_ChallengeInfo;
        $this->login = $login;
        $this->layout = $layout;
        $this->mlAct = $mlAct;
        $this->umConfig = $umConfig;
        $this->umState = $umState;
        $this->gameInfos = &$_GameInfos;
        $this->challengeInfo = $_ChallengeInfo;
    }
}