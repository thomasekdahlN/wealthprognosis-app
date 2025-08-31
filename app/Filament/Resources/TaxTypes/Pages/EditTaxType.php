<?php

namespace App\Filament\Resources\TaxTypes\Pages;

use App\Filament\Resources\TaxTypes\TaxTypeResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditTaxType extends EditRecord
{
    protected static string $resource = TaxTypeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
