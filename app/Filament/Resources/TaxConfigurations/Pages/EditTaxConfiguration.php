<?php

namespace App\Filament\Resources\TaxConfigurations\Pages;

use App\Filament\Resources\TaxConfigurations\TaxConfigurationResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditTaxConfiguration extends EditRecord
{
    protected static string $resource = TaxConfigurationResource::class;

    protected function getHeaderWidgets(): array
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
