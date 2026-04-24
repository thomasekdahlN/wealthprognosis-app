<?php

namespace App\Filament\System\Resources\AssetTypes\Pages;

use App\Filament\System\Resources\AssetTypes\AssetTypeResource;
use Filament\Resources\Pages\CreateRecord;

class CreateAssetType extends CreateRecord
{
    protected static string $resource = AssetTypeResource::class;
}
