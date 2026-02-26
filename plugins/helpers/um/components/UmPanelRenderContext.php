<?php


class UmPanelRenderContext {
    public $login;
    public $layout;
    public $players;
    public $selectedRow;
    public $selectedPlayerForLogin;
    public $actionIds;
    public $mlAct;
    public $umConfig;
    public $umState;

    // Derived once per render:
    public $activeTabAction = '';
    public $activeSubtabAction = '';

    public function __construct(
        $login,
        Layout $layout,
        array $players,
        $selectedRow,
        $selectedPlayerForLogin,
        array $actionIds,
        array $mlAct,
        UMConfig $umConfig,
        UmState $umState
    ) {
        $this->login = $login;
        $this->layout = $layout;
        $this->players = $players;
        $this->selectedRow = (int)$selectedRow;
        $this->selectedPlayerForLogin = $selectedPlayerForLogin;
        $this->actionIds = $actionIds;
        $this->mlAct = $mlAct;
        $this->umConfig = $umConfig;
        $this->umState = $umState;
    }
}