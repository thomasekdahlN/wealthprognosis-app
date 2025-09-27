<?php

namespace App\Filament\Resources\Assets\Pages;

use App\Filament\Resources\Assets\AssetResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditAsset extends EditRecord
{
    protected static string $resource = AssetResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }

    public static function getRoutes(): array
    {
        return [
            '/config/{configuration}/assets/{record}/edit' => static::class,
        ];
    }

    public static function getRouteKeyName(): ?string
    {
        return 'record';
    }
}
