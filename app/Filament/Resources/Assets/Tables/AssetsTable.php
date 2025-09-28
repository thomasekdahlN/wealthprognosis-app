<?php

namespace App\Filament\Resources\Assets\Tables;

use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class AssetsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->persistFiltersInSession(false)
            ->persistSearchInSession(false)
            ->persistSortInSession(false)
            ->persistColumnSearchesInSession(false)
            ->defaultSort('id')
            ->paginationPageOptions([50, 100, 150])
            ->defaultPaginationPageOption(50)
            ->columns([
                TextColumn::make('id')->label('ID')->sortable()->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('name')->label('Name')->searchable(),
                TextColumn::make('assetType.name')->label('Asset Type')->sortable()->searchable()->badge()->color('primary'),
                TextColumn::make('group')->label('Group')->searchable()->badge()->color(fn (string $state): string => match ($state) {
                    'private' => 'success',
                    'company' => 'warning',
                    default => 'gray',
                }),
                TextColumn::make('assetType.taxType.name')->label('Tax Type')->badge()->color('info')->placeholder('No tax type')->sortable()->searchable(),
                TextColumn::make('tax_property')->label('Tax Property')->searchable(),
                TextColumn::make('description')->label('Description')->formatStateUsing(fn (?string $state): string => strip_tags((string) $state))->limit(60)->wrap(),
                IconColumn::make('is_active')->label('Active')->boolean(),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filtersLayout(\Filament\Tables\Enums\FiltersLayout::AboveContent)
            ->filters([
                \Filament\Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Status')
                    ->placeholder('All')
                    ->trueLabel('Active only')
                    ->falseLabel('Inactive only'),

                \Filament\Tables\Filters\SelectFilter::make('asset_type')
                    ->label('Asset Type')
                    ->options(fn () => \App\Models\AssetType::query()->active()->ordered()->pluck('name', 'type')->all())
                    ->multiple()
                    ->preload(),
                \Filament\Tables\Filters\SelectFilter::make('group')
                    ->label('Group')
                    ->options(\App\Models\Asset::GROUPS),

                \Filament\Tables\Filters\Filter::make('tax_type')
                    ->label('Tax Type')
                    ->form([
                        \Filament\Forms\Components\Select::make('tax_type_id')
                            ->label('Tax Type')
                            ->options(fn () => \App\Models\TaxType::query()->active()->ordered()->pluck('name', 'id')->all())
                            ->searchable()
                            ->preload(),
                    ])
                    ->query(function ($query, array $data) {
                        if (! empty($data['tax_type_id'])) {
                            $query->whereHas('assetType', fn ($q) => $q->where('tax_type_id', $data['tax_type_id']));
                        }

                        return $query;
                    }),

                \Filament\Tables\Filters\TernaryFilter::make('liquid')
                    ->label('Liquidity')
                    ->placeholder('All')
                    ->trueLabel('Liquid')
                    ->falseLabel('Illiquid')
                    ->queries(
                        true: fn ($query) => $query->whereHas('assetType', fn ($q) => $q->where('is_liquid', true)),
                        false: fn ($query) => $query->whereHas('assetType', fn ($q) => $q->where('is_liquid', false)),
                        blank: fn ($query) => $query,
                    ),

                \Filament\Tables\Filters\TernaryFilter::make('cap_income')
                    ->label('Can Generate Income')
                    ->placeholder('All')
                    ->trueLabel('Yes')
                    ->falseLabel('No')
                    ->queries(
                        true: fn ($query) => $query->whereHas('assetType', fn ($q) => $q->where('can_generate_income', true)),
                        false: fn ($query) => $query->whereHas('assetType', fn ($q) => $q->where('can_generate_income', false)),
                        blank: fn ($query) => $query,
                    ),

                \Filament\Tables\Filters\TernaryFilter::make('cap_expenses')
                    ->label('Can Generate Expenses')
                    ->placeholder('All')
                    ->trueLabel('Yes')
                    ->falseLabel('No')
                    ->queries(
                        true: fn ($query) => $query->whereHas('assetType', fn ($q) => $q->where('can_generate_expenses', true)),
                        false: fn ($query) => $query->whereHas('assetType', fn ($q) => $q->where('can_generate_expenses', false)),
                        blank: fn ($query) => $query,
                    ),

                \Filament\Tables\Filters\TernaryFilter::make('cap_mortgage')
                    ->label('Can Have Mortgage')
                    ->placeholder('All')
                    ->trueLabel('Yes')
                    ->falseLabel('No')
                    ->queries(
                        true: fn ($query) => $query->whereHas('assetType', fn ($q) => $q->where('can_have_mortgage', true)),
                        false: fn ($query) => $query->whereHas('assetType', fn ($q) => $q->where('can_have_mortgage', false)),
                        blank: fn ($query) => $query,
                    ),

                \Filament\Tables\Filters\TernaryFilter::make('cap_market_value')
                    ->label('Can Have Market Value')
                    ->placeholder('All')
                    ->trueLabel('Yes')
                    ->falseLabel('No')
                    ->queries(
                        true: fn ($query) => $query->whereHas('assetType', fn ($q) => $q->where('can_have_market_value', true)),
                        false: fn ($query) => $query->whereHas('assetType', fn ($q) => $q->where('can_have_market_value', false)),
                        blank: fn ($query) => $query,
                    ),

            ])

            ->recordUrl(function (\App\Models\Asset $record): string {
                $configurationId = (int) (request()->route('configuration') ?? 0);

                if ($configurationId > 0) {
                    return route('filament.admin.pages.config-asset-years.pretty', [
                        'configuration' => $configurationId,
                        'asset' => $record->getKey(),
                    ]);
                }

                return \App\Filament\Resources\Assets\AssetResource::getUrl('edit', [
                    'record' => $record,
                ]);
            })
            ->toolbarActions([]);
    }
}
