<?php

namespace App\Filament\Resources\SimulationConfigurations\Pages;

use App\Filament\Resources\SimulationConfigurations\SimulationConfigurationResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditSimulationConfiguration extends EditRecord
{
    protected static string $resource = SimulationConfigurationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
