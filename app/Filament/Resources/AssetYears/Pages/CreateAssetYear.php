<?php

namespace App\Filament\Resources\AssetYears\Pages;

use App\Filament\Resources\AssetYears\AssetYearResource;
use Filament\Resources\Pages\CreateRecord;

class CreateAssetYear extends CreateRecord
{
    protected static string $resource = AssetYearResource::class;

    public function getBreadcrumbs(): array
    {
        return [];
    }
}
