<?php

class LeaderboardPanel {
    static function render($ctx) {
        $selectedPlayer = isset($ctx->umState->selectedPlayer[$ctx->login])
            ? $ctx->umState->selectedPlayer[$ctx->login]
            : null;

        if ($selectedPlayer === null) {
            if (!isset($ctx->umState->selectedPlayerCollection[$ctx->login])) {
                return RightPanel::buildTitle($ctx->layout, "Leaderboard Detailed Rankings");
            }

            return RightPanel::buildTitle($ctx->layout, "Leaderboard Detailed Rankings")
                . LeaderboardPlayersTable::build($ctx, $ctx->umState->selectedPlayerCollection[$ctx->login]);
        }

        return RightPanel::buildTitle($ctx->layout, $selectedPlayer['NickNameWithColor'])
            . PlayerDetailsTable::build($ctx, $selectedPlayer);
    }
}