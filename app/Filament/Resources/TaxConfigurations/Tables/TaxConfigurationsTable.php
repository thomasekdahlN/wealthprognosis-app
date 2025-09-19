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
                    ->searchable()
                    ->sortable()
                    ->badge(),
                TextColumn::make('description')
                    ->label('Description')
                    ->searchable()
                    ->sortable()
                    ->wrap(),
                TextColumn::make('updated_at')
                    ->label('Last Updated')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('updatedBy.name')
                    ->label('Updated By')
                    ->badge()
                    ->searchable()
                    ->sortable(),
            ])
            ->filtersLayout(FiltersLayout::AboveContent)
            ->filters([])

            ->searchable()

            ->toggleColumnsTriggerAction(fn ($action) => $action->modalHeading('Choose columns'))
            ->defaultSort('tax_type', 'asc')
            ->recordUrl(fn ($record) => TaxConfigurationResource::getUrl('edit', [
                'country' => $record->country_code,
                'year' => $record->year,
                'record' => $record,
            ]))
            ->emptyStateHeading('Choose country and year to view tax configurations')
            ->toolbarActions([])
            ->paginated([50, 100, 150])
            ->defaultPaginationPageOption(50)
            ->paginationPageOptions([50, 100, 150]);
    }
}
