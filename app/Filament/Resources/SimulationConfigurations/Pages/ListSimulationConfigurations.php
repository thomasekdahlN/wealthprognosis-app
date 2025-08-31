<?php

namespace App\Filament\Resources\SimulationConfigurations\Pages;

use App\Filament\Resources\SimulationConfigurations\SimulationConfigurationResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListSimulationConfigurations extends ListRecords
{
    protected static string $resource = SimulationConfigurationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
