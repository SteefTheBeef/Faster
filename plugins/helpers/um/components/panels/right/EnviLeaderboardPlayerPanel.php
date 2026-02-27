<?php

class EnviLeaderboardPlayerPanel {
    static function render($ctx) {
        $selectedPlayer = $ctx->umState->selectedPlayer[$ctx->login];
        console('selected player: ' . print_r($selectedPlayer, true));
        return $selectedPlayer ? RightPanel::buildTitle($ctx->layout, $selectedPlayer['NickNameWithColor'])
            . PlayerEnviDetailsTable::build($ctx, $selectedPlayer) : RightPanel::buildTitle($ctx->layout, 'No Player Selected...');
    }
}