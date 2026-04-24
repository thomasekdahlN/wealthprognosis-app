<?php

namespace App\Filament\System\Resources\TaxTypes\Pages;

use App\Filament\System\Resources\TaxTypes\TaxTypeResource;
use Filament\Resources\Pages\CreateRecord;

class CreateTaxType extends CreateRecord
{
    protected static string $resource = TaxTypeResource::class;
}
