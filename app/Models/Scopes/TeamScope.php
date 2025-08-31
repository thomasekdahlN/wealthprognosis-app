<?php

namespace App\Models\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;
use Illuminate\Support\Facades\Auth;

class TeamScope implements Scope
{
    /**
     * Apply the scope to a given Eloquent query builder.
     */
    public function apply(Builder $builder, Model $model): void
    {
        // Only apply team filtering if user is authenticated and has a current team
        $user = Auth::user();

        if ($user && $user->current_team_id) {
            $builder->where($model->getTable().'.team_id', $user->current_team_id);
        }
    }
}
