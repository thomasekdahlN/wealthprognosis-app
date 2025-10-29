<?php

namespace App\Filament\Resources\TaxConfigurations\Pages;

use App\Filament\Resources\TaxConfigurations\TaxConfigurationResource;
use App\Filament\Resources\TaxConfigurations\Widgets\StandardDeductionWidget;
use App\Filament\Resources\TaxConfigurations\Widgets\TaxRateTrendWidget;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditTaxConfiguration extends EditRecord
{
    protected static string $resource = TaxConfigurationResource::class;

    protected function getHeaderWidgets(): array
    {
        return [
            TaxRateTrendWidget::make([
                'record' => $this->record,
            ]),
            StandardDeductionWidget::make([
                'record' => $this->record,
            ]),
        ];
    }

    protected function getRedirectUrl(): ?string
    {
        return null; // Stay on the same page after save
    }

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make()
                ->successRedirectUrl(fn () => static::getResource()::getUrl('list', [
                    'country' => $this->record->country_code ?? request()->route('country'),
                    'year' => $this->record->year ?? request()->route('year'),
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
            'country' => $this->record->country_code ?? request()->route('country'),
            'year' => $this->record->year ?? request()->route('year'),
        ]);
    }
}
