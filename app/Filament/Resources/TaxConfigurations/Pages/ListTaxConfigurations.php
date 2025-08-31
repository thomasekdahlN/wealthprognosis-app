<?php

namespace App\Filament\Resources\TaxConfigurations\Pages;

use App\Filament\Resources\TaxConfigurations\TaxConfigurationResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListTaxConfigurations extends ListRecords
{
    public string $country;

    public string $year;

    protected function getTableQuery(): \Illuminate\Database\Eloquent\Builder
    {
        $this->country = (string) request()->route('country');
        $this->year = (string) request()->route('year');

        return parent::getTableQuery()
            ->where('country_code', $this->country)
            ->where('year', (int) $this->year);
    }

    protected static string $resource = TaxConfigurationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label('Add Tax Configuration')
                ->visible(fn () => filled($this->country) && filled($this->year))
                ->url(fn () => static::getResource()::getUrl('create', [
                    'country' => $this->country,
                    'year' => $this->year,
                ])),
        ];
    }

    public function getBreadcrumbs(): array
    {
        return [];
    }
}
