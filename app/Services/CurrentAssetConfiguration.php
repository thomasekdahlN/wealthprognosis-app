<?php

namespace App\Services;

use App\Models\AssetConfiguration;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Container\Container;
use Illuminate\Support\Facades\Session;

class CurrentAssetConfiguration
{
    private const SESSION_KEY = 'active_asset_configuration_id';

    public function get(): ?AssetConfiguration
    {
        $id = Session::get(self::SESSION_KEY);
        return $id ? AssetConfiguration::find($id) : null;
    }

    public function id(): ?int
    {
        return Session::get(self::SESSION_KEY);
    }

    public function set(?AssetConfiguration $config): void
    {
        if ($config) {
            Session::put(self::SESSION_KEY, $config->id);
        } else {
            Session::forget(self::SESSION_KEY);
        }
    }

    public function has(): bool
    {
        return Session::has(self::SESSION_KEY) && $this->get() !== null;
    }
}

