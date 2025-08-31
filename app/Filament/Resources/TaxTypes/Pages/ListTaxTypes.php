<?php

namespace App\Filament\Resources\TaxTypes\Pages;

use App\Filament\Resources\TaxTypes\TaxTypeResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListTaxTypes extends ListRecords
{
    protected static string $resource = TaxTypeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
