<?php

namespace VentureDrake\LaravelCrm\Support;

use VentureDrake\LaravelCrm\Contracts\ResolvesTeam;

/**
 * One place that answers the current team id for BelongsToTeams and
 * BelongsToTeamsScope, so the two cannot drift apart.
 */
class TeamResolution
{
    public static function currentTeamId(): ?int
    {
        $resolver = config('laravel-crm.team_resolver') ?: CurrentUserTeamResolver::class;
        $instance = app($resolver);

        if (! $instance instanceof ResolvesTeam) {
            throw new \UnexpectedValueException(
                'laravel-crm.team_resolver must name a class implementing '.ResolvesTeam::class.', got '.$resolver
            );
        }

        return $instance->currentTeamId();
    }
}
