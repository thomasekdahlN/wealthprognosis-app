<?php

namespace App\Filament\Resources\TaxConfigurations\Pages;

use App\Filament\Resources\TaxConfigurations\TaxConfigurationResource;
use App\Filament\Resources\TaxConfigurations\Widgets\IncomeTaxRateTrend;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditTaxConfiguration extends EditRecord
{
    protected static string $resource = TaxConfigurationResource::class;

    protected function getHeaderWidgets(): array
    {
        $record = $this->getRecord();

        return [
            IncomeTaxRateTrend::make([
                'country' => (string) $record->country_code,
                'tax_type' => (string) $record->asset_type,
            ]),
        ];
    }

    public function getRedirectUrl(): string
    {
        return static::getResource()::getUrl('list', [
            'country' => request()->route('country'),
            'year' => request()->route('year'),
        ]);
    }

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make()
                ->successRedirectUrl(fn () => static::getResource()::getUrl('list', [
                    'country' => request()->route('country'),
                    'year' => request()->route('year'),
                ])),
        ];
    }

    public function getBreadcrumbs(): array
    {
        return [];
    }

    protected function getCancelFormActionUrl(): ?string
    {
        return static::getResource()::getUrl('list', [
            'country' => request()->route('country'),
            'year' => request()->route('year'),
        ]);
    }
}
