<?php

namespace VentureDrake\LaravelCrm\Traits;

use Illuminate\Database\Eloquent\Model;
use VentureDrake\LaravelCrm\Scopes\BelongsToTeamsScope;
use VentureDrake\LaravelCrm\Support\TeamResolution;

/**
 * @mixin Model
 */
trait BelongsToTeams
{
    /**
     * Boot the belongs to teams trait for a model.
     *
     * @return void
     */
    public static function bootBelongsToTeams()
    {
        static::addGlobalScope(new BelongsToTeamsScope);

        static::creating(function (Model $model) {
            if (config('laravel-crm.teams') && ! $model->team_id && ($teamId = TeamResolution::currentTeamId()) !== null) {
                $model->team_id = $teamId;
            }
        });
    }
}
