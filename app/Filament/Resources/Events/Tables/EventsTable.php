<?php

namespace App\Filament\Resources\Events\Tables;

use App\Helpers\AmountHelper;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\TextInputColumn;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Table;

class EventsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('year')
                    ->label('Year')
                    ->sortable()
                    ->alignLeft()
                    ->toggleable(false),
                TextColumn::make('asset.name')
                    ->label('Asset')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('asset.asset_type')
                    ->label('Type')
                    ->badge()
                    ->color('primary'),
                TextColumn::make('asset.assetType.name')
                    ->label('Asset Type')
                    ->sortable(),
                TextInputColumn::make('income_amount')
                    ->label('Income Amount')
                    ->grow(false)
                    ->type('text')
                    ->rules(['nullable', 'string'])
                    ->getStateUsing(fn ($record) => AmountHelper::formatNorwegian($record->income_amount))
                    ->updateStateUsing(function ($record, $state) {
                        $amount = AmountHelper::parseNorwegianAmount((string) $state);
                        $record->income_amount = $amount ?? 0;
                        $record->save();

                        return AmountHelper::formatNorwegian($amount);
                    })
                    ->alignRight()
                    ->extraAttributes(function () {
                        $attrs = AmountHelper::getNorwegianAmountMask();
                        $attrs['style'] = ($attrs['style'] ?? '').' width: 12rem;';

                        return $attrs;
                    })
                    ->sortable(),
                TextInputColumn::make('expence_amount')
                    ->label('Expense Amount')
                    ->grow(false)
                    ->type('text')
                    ->rules(['nullable', 'string'])
                    ->getStateUsing(fn ($record) => AmountHelper::formatNorwegian($record->expence_amount))
                    ->updateStateUsing(function ($record, $state) {
                        $amount = AmountHelper::parseNorwegianAmount((string) $state);
                        $record->expence_amount = $amount ?? 0;
                        $record->save();

                        return AmountHelper::formatNorwegian($amount);
                    })
                    ->alignRight()
                    ->extraAttributes(function () {
                        $attrs = AmountHelper::getNorwegianAmountMask();
                        $attrs['style'] = ($attrs['style'] ?? '').' width: 12rem;';

                        return $attrs;
                    })
                    ->sortable(),
                TextInputColumn::make('asset_market_amount')
                    ->label('Asset Market Amount')
                    ->grow(false)
                    ->type('text')
                    ->rules(['nullable', 'string'])
                    ->getStateUsing(fn ($record) => AmountHelper::formatNorwegian($record->asset_market_amount))
                    ->updateStateUsing(function ($record, $state) {
                        $amount = AmountHelper::parseNorwegianAmount((string) $state);
                        $record->asset_market_amount = $amount ?? 0;
                        $record->save();

                        return AmountHelper::formatNorwegian($amount);
                    })
                    ->alignRight()
                    ->extraAttributes(function () {
                        $attrs = AmountHelper::getNorwegianAmountMask();
                        $attrs['style'] = ($attrs['style'] ?? '').' width: 12rem;';

                        return $attrs;
                    })
                    ->sortable(),
                TextInputColumn::make('mortgage_amount')
                    ->label('Mortgage Amount')
                    ->grow(false)
                    ->type('text')
                    ->rules(['nullable', 'string'])
                    ->getStateUsing(fn ($record) => AmountHelper::formatNorwegian($record->mortgage_amount))
                    ->updateStateUsing(function ($record, $state) {
                        $amount = AmountHelper::parseNorwegianAmount((string) $state);
                        $record->mortgage_amount = $amount ?? 0;
                        $record->save();

                        return AmountHelper::formatNorwegian($amount);
                    })
                    ->alignRight()
                    ->extraAttributes(function () {
                        $attrs = AmountHelper::getNorwegianAmountMask();
                        $attrs['style'] = ($attrs['style'] ?? '').' width: 12rem;';

                        return $attrs;
                    })
                    ->sortable(),
                TextInputColumn::make('description')
                    ->label('Description')
                    ->type('text')
                    ->rules(['nullable', 'string'])
                    ->getStateUsing(fn ($record) => $record->description ? strip_tags($record->description) : '')
                    ->updateStateUsing(function ($record, $state) {
                        $record->description = $state;
                        $record->save();

                        return $state;
                    })
                    ->columnSpanFull(),
                IconColumn::make('asset_is_active')
                    ->label('Active')
                    ->boolean()
                    ->getStateUsing(fn ($record) => (bool) ($record->asset?->is_active)),
            ])
            ->filters([])
            ->filtersLayout(FiltersLayout::AboveContent)
            ->toggleColumnsTriggerAction(fn ($action) => $action->modalHeading('Choose columns'))
            ->defaultSort('year', 'asc')
            ->paginated([50, 100, 150])
            ->defaultPaginationPageOption(50)
            ->paginationPageOptions([50, 100, 150])
            ->striped()
            ->persistFiltersInSession()
            ->recordUrl(function ($record): string {
                $configurationId = (int) (app(\App\Services\CurrentAssetConfiguration::class)->id() ?? 0);

                return \App\Filament\Resources\AssetYears\AssetYearResource::getUrl('edit', [
                    'record' => $record->getKey(),
                    'configuration' => $configurationId,
                ]);
            });
    }
}
