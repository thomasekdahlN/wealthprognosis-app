<?php

namespace App\Filament\Resources\ChangeRateConfigurations\Pages;

use App\Filament\Resources\ChangeRateConfigurations\ChangeRateConfigurationResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditChangeRateConfiguration extends EditRecord
{
    protected static string $resource = ChangeRateConfigurationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
