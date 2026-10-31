<?php

namespace VentureDrake\LaravelCrm\Contracts;

/**
 * Answers "which team is the current request acting for?" for team scoping
 * and team stamping (BelongsToTeams, BelongsToTeamsScope).
 *
 * Bound by class name in `laravel-crm.team_resolver` (a class name, not a
 * closure: `config:cache` writes the config with var_export, and a closure
 * does not survive that). When the key is null the package reads
 * `auth()->user()->currentTeam`, exactly as before.
 */
interface ResolvesTeam
{
    /**
     * The current team id, or null when the request acts for no team —
     * in which case nothing is scoped and nothing is stamped.
     */
    public function currentTeamId(): ?int;
}
