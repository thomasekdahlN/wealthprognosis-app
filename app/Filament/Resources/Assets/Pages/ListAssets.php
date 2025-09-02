<?php

namespace App\Filament\Resources\Assets\Pages;

use App\Filament\Resources\Assets\AssetResource;
use Filament\Actions\CreateAction;
use Filament\Actions\Action;
use Filament\Resources\Pages\ListRecords;

class ListAssets extends ListRecords
{
    protected static string $resource = AssetResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
            Action::make('upload_asset_configuration')
                ->label('upload asset configuration')
                ->icon('heroicon-m-arrow-up-tray')
                ->color('primary')
                ->action(fn () => redirect(\App\Filament\Pages\AssetConfigurationUpload::getUrl()))
        ];
    }
}
