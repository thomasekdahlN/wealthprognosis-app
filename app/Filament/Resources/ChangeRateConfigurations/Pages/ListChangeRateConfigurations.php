<?php

namespace App\Filament\Resources\ChangeRateConfigurations\Pages;

use App\Filament\Resources\ChangeRateConfigurations\ChangeRateConfigurationResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListChangeRateConfigurations extends ListRecords
{
    protected static string $resource = ChangeRateConfigurationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
