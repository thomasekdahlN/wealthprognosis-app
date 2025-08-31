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
                    ->description(fn ($record) => $record->asset_description ? strip_tags($record->asset_description) : null)
                    ->wrap(),
                TextInputColumn::make('income_amount')
                    ->type('number')
                    ->step(0.01)
                    ->rules(['nullable', 'numeric', 'min:0'])
                    ->sortable()
                    ->alignRight()
                    ->extraAttributes(AmountHelper::getRightAlignedStyle()),
                TextInputColumn::make('expence_amount')
                    ->type('number')
                    ->step(0.01)
                    ->rules(['nullable', 'numeric', 'min:0'])
                    ->sortable()
                    ->alignRight()
                    ->extraAttributes(AmountHelper::getRightAlignedStyle()),
                TextInputColumn::make('asset_market_amount')
                    ->type('number')
                    ->step(0.01)
                    ->rules(['nullable', 'numeric', 'min:0'])
                    ->sortable()
                    ->alignRight()
                    ->extraAttributes(AmountHelper::getRightAlignedStyle()),
                TextInputColumn::make('mortgage_amount')
                    ->type('number')
                    ->step(0.01)
                    ->rules(['nullable', 'numeric', 'min:0'])
                    ->sortable()
                    ->alignRight()
                    ->extraAttributes(AmountHelper::getRightAlignedStyle()),
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
            ->recordUrl(fn (\App\Models\AssetYear $record) => \App\Filament\Resources\AssetYears\AssetYearResource::getUrl('edit', ['record' => $record->getKey()]));
    }
}
