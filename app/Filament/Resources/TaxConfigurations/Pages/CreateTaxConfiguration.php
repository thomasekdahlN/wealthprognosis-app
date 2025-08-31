<?php

namespace App\Filament\Resources\TaxConfigurations\Pages;

use App\Filament\Resources\TaxConfigurations\TaxConfigurationResource;
use Filament\Resources\Pages\CreateRecord;

class CreateTaxConfiguration extends CreateRecord
{
    public function getBreadcrumbs(): array
    {
        return [];
    }

    public function getRedirectUrl(): string
    {
        return static::getResource()::getUrl('list', [
            'country' => request()->route('country'),
            'year' => request()->route('year'),
        ]);
    }

    protected function getCancelFormActionUrl(): ?string
    {
        return static::getResource()::getUrl('list', [
            'country' => request()->route('country'),
            'year' => request()->route('year'),
        ]);
    }

    protected static string $resource = TaxConfigurationResource::class;
}
