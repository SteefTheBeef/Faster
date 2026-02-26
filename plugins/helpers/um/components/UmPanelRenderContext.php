<?php


class UmPanelRenderContext {
    public $login;
    public $layout;

    public $selectPlayerActionIds;
    public $mlAct;
    public $umConfig;
    public $umState;

    // Derived once per render:
    public $activeTabAction = '';
    public $activeSubtabAction = '';

    public function __construct($login, Layout $layout, array $selectPlayerActionIds, array $mlAct, UMConfig $umConfig, UmState $umState) {
        $this->login = $login;
        $this->layout = $layout;
        $this->selectPlayerActionIds = $selectPlayerActionIds;
        $this->mlAct = $mlAct;
        $this->umConfig = $umConfig;
        $this->umState = $umState;
    }
}