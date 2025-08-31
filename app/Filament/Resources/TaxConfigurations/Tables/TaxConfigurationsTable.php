<?php

namespace App\Filament\Resources\TaxConfigurations\Tables;

use App\Filament\Resources\TaxConfigurations\TaxConfigurationResource;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Table;

class TaxConfigurationsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('tax_type')
                    ->label('Tax Type')
                    ->sortable()
                    ->badge(),
                TextColumn::make('description')
                    ->label('Description')
                    ->sortable()
                    ->wrap(),
                TextColumn::make('updated_at')
                    ->label('Last Updated')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('updated_by')
                    ->label('Updated By')
                    ->badge()
                    ->sortable()
                    ->formatStateUsing(function ($record) {
                        return optional($record->updatedBy)->name;
                    }),
            ])
            ->filtersLayout(FiltersLayout::AboveContent)
            ->filters([])

            ->toggleColumnsTriggerAction(fn ($action) => $action->modalHeading('Choose columns'))
            ->defaultSort('tax_type', 'asc')
            ->recordUrl(function ($record) {
                $country = request()->route('country');
                $year = request()->route('year');

                return TaxConfigurationResource::getUrl('edit', [
                    'country' => $country,
                    'year' => $year,
                    'record' => $record,
                ]);
            })
            ->emptyStateHeading('Choose country and year to view tax configurations')
            ->toolbarActions([])
            ->paginated([50, 100, 150])
            ->defaultPaginationPageOption(50)
            ->paginationPageOptions([50, 100, 150]);
    }
}
