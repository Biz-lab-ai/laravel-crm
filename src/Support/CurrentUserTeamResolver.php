<?php

namespace VentureDrake\LaravelCrm\Support;

use VentureDrake\LaravelCrm\Contracts\ResolvesTeam;

/**
 * The package default: the signed-in user's current team, exactly what
 * BelongsToTeams and BelongsToTeamsScope read before the resolver existed.
 */
class CurrentUserTeamResolver implements ResolvesTeam
{
    public function currentTeamId(): ?int
    {
        if (auth()->hasUser() && auth()->user()->currentTeam) {
            return auth()->user()->currentTeam->id ?? null;
        }

        return null;
    }
}
