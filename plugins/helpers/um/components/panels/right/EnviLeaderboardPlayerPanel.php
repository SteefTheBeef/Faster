<?php

class EnviLeaderboardPlayerPanel {
    static function render($ctx) {
        if (!isset($ctx->umState->selectedPlayer[$ctx->login])) {
            return RightPanel::buildTitle($ctx->layout, 'No Player Selected...');
        }
        $selectedPlayer = $ctx->umState->selectedPlayer[$ctx->login];
        return $selectedPlayer ? RightPanel::buildTitle($ctx->layout, $selectedPlayer['NickNameWithColor'])
            . PlayerEnviDetailsTable::build($ctx, $selectedPlayer) : RightPanel::buildTitle($ctx->layout, 'No Player Selected...');
    }
}