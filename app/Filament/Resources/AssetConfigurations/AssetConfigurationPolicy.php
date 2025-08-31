<?php

namespace App\Filament\Resources\AssetConfigurations;

use App\Models\AssetConfiguration;
use App\Models\User;

class AssetConfigurationPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, AssetConfiguration $model): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, AssetConfiguration $model): bool
    {
        return true;
    }

    public function delete(User $user, AssetConfiguration $model): bool
    {
        return false;
    }
}
