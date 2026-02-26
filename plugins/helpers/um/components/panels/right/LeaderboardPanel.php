<?php

class LeaderboardPanel {
    static function render($ctx) {
        //console('selected player: ' . print_r($ctx->umState->selectedPlayer[$ctx->login], true));
        $selectedPlayer = $ctx->umState->selectedPlayer[$ctx->login];
        if ($selectedPlayer === null) {
            return RightPanel::buildTitle($ctx->layout, "Leaderboard Detailed Rankings")
                . LeaderboardPlayersTable::build($ctx, $ctx->umState->selectedPlayerCollection[$ctx->login]);
        }

        return RightPanel::buildTitle($ctx->layout, $selectedPlayer['NickNameWithColor'])
            . PlayerDetailsTable::build($ctx, $selectedPlayer);
    }
}