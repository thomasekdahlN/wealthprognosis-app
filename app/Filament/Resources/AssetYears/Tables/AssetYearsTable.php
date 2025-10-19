<?php

namespace App\Filament\Resources\AssetYears\Tables;

use App\Helpers\AmountHelper;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\TextInputColumn;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Table;

class AssetYearsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('year')
                    ->sortable()
                    ->description(fn ($record) => $record->description ? strip_tags($record->description) : null)
                    ->wrap(),
                TextInputColumn::make('income_amount')
                    ->type('text')
                    ->rules(['nullable', 'string'])
                    ->sortable()
                    ->alignRight()
                    ->extraAttributes(AmountHelper::getRightAlignedStyle())
                    ->getStateUsing(fn ($record) => AmountHelper::formatNorwegian($record->income_amount))
                    ->updateStateUsing(function ($record, $state) {
                        $amount = AmountHelper::parseNorwegianAmount((string) $state);
                        $record->update(['income_amount' => $amount]);

                        return AmountHelper::formatNorwegian($amount);
                    }),
                TextInputColumn::make('expence_amount')
                    ->type('text')
                    ->rules(['nullable', 'string'])
                    ->sortable()
                    ->alignRight()
                    ->extraAttributes(AmountHelper::getRightAlignedStyle())
                    ->getStateUsing(fn ($record) => AmountHelper::formatNorwegian($record->expence_amount))
                    ->updateStateUsing(function ($record, $state) {
                        $amount = AmountHelper::parseNorwegianAmount((string) $state);
                        $record->update(['expence_amount' => $amount]);

                        return AmountHelper::formatNorwegian($amount);
                    }),
                TextInputColumn::make('asset_market_amount')
                    ->type('text')
                    ->rules(['nullable', 'string'])
                    ->sortable()
                    ->alignRight()
                    ->extraAttributes(AmountHelper::getRightAlignedStyle())
                    ->getStateUsing(fn ($record) => AmountHelper::formatNorwegian($record->asset_market_amount))
                    ->updateStateUsing(function ($record, $state) {
                        $amount = AmountHelper::parseNorwegianAmount((string) $state);
                        $record->update(['asset_market_amount' => $amount]);

                        return AmountHelper::formatNorwegian($amount);
                    }),
                TextInputColumn::make('mortgage_amount')
                    ->type('text')
                    ->rules(['nullable', 'string'])
                    ->sortable()
                    ->alignRight()
                    ->extraAttributes(AmountHelper::getRightAlignedStyle())
                    ->getStateUsing(fn ($record) => AmountHelper::formatNorwegian($record->mortgage_amount))
                    ->updateStateUsing(function ($record, $state) {
                        $amount = AmountHelper::parseNorwegianAmount((string) $state);
                        $record->update(['mortgage_amount' => $amount]);

                        return AmountHelper::formatNorwegian($amount);
                    }),
                TextColumn::make('created_at')->dateTime()->sortable()->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')->dateTime()->sortable()->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filtersLayout(FiltersLayout::AboveContent)
            ->filters([])
            ->toggleColumnsTriggerAction(fn ($action) => $action->modalHeading('Choose columns'))
            ->defaultSort('year')
            ->paginated([50, 100, 150])
            ->defaultPaginationPageOption(50)
            ->paginationPageOptions([50, 100, 150])
            ->recordUrl(fn (\App\Models\AssetYear $record) => \App\Filament\Resources\AssetYears\AssetYearResource::getUrl('edit', [
                'record' => $record->getKey(),
                'configuration' => $record->asset_configuration_id,
            ]));
    }
}
