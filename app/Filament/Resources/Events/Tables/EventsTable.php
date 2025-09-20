<?php

namespace App\Filament\Resources\Events\Tables;

use App\Helpers\AmountHelper;
use App\Models\AssetType;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\TextInputColumn;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class EventsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('futureYear')
                    ->label('Year')
                    ->getStateUsing(function ($record) {
                        $currentYear = (int) date('Y');
                        $futureYear = $record->years()
                            ->where('year', '>', $currentYear)
                            ->orderBy('year')
                            ->first();

                        return $futureYear ? $futureYear->year : '-';
                    })
                    ->sortable(query: function ($query, string $direction) {
                        $currentYear = (int) date('Y');

                        return $query->leftJoin('asset_years', function ($join) use ($currentYear) {
                                $join->on('assets.id', '=', 'asset_years.asset_id')
                                     ->where('asset_years.year', '>', $currentYear);
                            })
                            ->select('assets.*')
                            ->groupBy('assets.id')
                            ->orderByRaw('MIN(asset_years.year) ' . $direction . ' NULLS LAST');
                    })
                    ->alignLeft()
                    ->toggleable(false),
                TextColumn::make('name')
                    ->label('Asset Name')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('asset_type')
                    ->label('Type')
                    ->searchable()
                    ->sortable()
                    ->badge()
                    ->color('primary'),
                TextColumn::make('assetType.name')
                    ->label('Asset Type')
                    ->sortable(),
                TextInputColumn::make('futureIncomeAmount')
                    ->label('Income Amount')
                    ->getStateUsing(function ($record) {
                        $currentYear = (int) date('Y');
                        $futureYear = $record->years()
                            ->where('year', '>', $currentYear)
                            ->orderBy('year')
                            ->first();

                        return $futureYear ? AmountHelper::formatNorwegian($futureYear->income_amount) : null;
                    })
                    ->type('text')
                    ->rules(['nullable', 'string'])
                    ->alignRight()
                    ->extraAttributes(AmountHelper::getNorwegianAmountMask())
                    ->sortable(),
                TextInputColumn::make('futureExpenseAmount')
                    ->label('Expense Amount')
                    ->getStateUsing(function ($record) {
                        $currentYear = (int) date('Y');
                        $futureYear = $record->years()
                            ->where('year', '>', $currentYear)
                            ->orderBy('year')
                            ->first();

                        return $futureYear ? AmountHelper::formatNorwegian($futureYear->expence_amount) : null;
                    })
                    ->type('text')
                    ->rules(['nullable', 'string'])
                    ->alignRight()
                    ->extraAttributes(AmountHelper::getNorwegianAmountMask())
                    ->sortable(),
                TextInputColumn::make('futureAssetMarketAmount')
                    ->label('Asset Market Amount')
                    ->getStateUsing(function ($record) {
                        $currentYear = (int) date('Y');
                        $futureYear = $record->years()
                            ->where('year', '>', $currentYear)
                            ->orderBy('year')
                            ->first();

                        return $futureYear ? AmountHelper::formatNorwegian($futureYear->asset_market_amount) : null;
                    })
                    ->type('text')
                    ->rules(['nullable', 'string'])
                    ->alignRight()
                    ->extraAttributes(AmountHelper::getNorwegianAmountMask())
                    ->sortable(),
                TextInputColumn::make('futureMortgageAmount')
                    ->label('Mortgage Amount')
                    ->getStateUsing(function ($record) {
                        $currentYear = (int) date('Y');
                        $futureYear = $record->years()
                            ->where('year', '>', $currentYear)
                            ->orderBy('year')
                            ->first();

                        return $futureYear ? AmountHelper::formatNorwegian($futureYear->mortgage_amount) : null;
                    })
                    ->type('text')
                    ->rules(['nullable', 'string'])
                    ->alignRight()
                    ->extraAttributes(AmountHelper::getNorwegianAmountMask())
                    ->sortable(),
                IconColumn::make('is_active')
                    ->label('Active')
                    ->boolean(),
                TextColumn::make('description')
                    ->label('Description')
                    ->getStateUsing(function ($record) {
                        $currentYear = (int) date('Y');
                        $futureYear = $record->years()
                            ->where('year', '>', $currentYear)
                            ->orderBy('year')
                            ->first();

                        return $futureYear?->description ? strip_tags($futureYear->description) : '';
                    })
                    ->columnSpanFull()
                    ->wrap()
                    ->html(false),
            ])
            ->filters([
                SelectFilter::make('asset_type')
                    ->label('Asset Type')
                    ->options(fn () => AssetType::query()->active()->ordered()->pluck('name', 'type')->all())
                    ->multiple()
                    ->preload(),
                TernaryFilter::make('is_active')
                    ->label('Status')
                    ->placeholder('All')
                    ->trueLabel('Active only')
                    ->falseLabel('Inactive only'),
            ])
            ->filtersLayout(FiltersLayout::AboveContent)
            ->toggleColumnsTriggerAction(fn ($action) => $action->modalHeading('Choose columns'))
            ->defaultSort('futureYear', 'asc')
            ->paginated([50, 100, 150])
            ->defaultPaginationPageOption(50)
            ->paginationPageOptions([50, 100, 150])
            ->striped()
            ->persistFiltersInSession()
            ->recordUrl(fn (\App\Models\Asset $record) => route('filament.admin.resources.asset-years.index', [
                'asset' => $record->id,
            ]));
    }
}
