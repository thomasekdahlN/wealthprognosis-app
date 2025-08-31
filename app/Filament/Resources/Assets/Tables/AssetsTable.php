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
            ->columns([
                TextColumn::make('name')->label('Name')->searchable(),
                TextColumn::make('assetType.name')->label('Asset Type')->sortable()->searchable()->badge()->color('primary'),
                TextColumn::make('group')->label('Group')->searchable()->badge()->color(fn (string $state): string => match ($state) {
                    'private' => 'success',
                    'company' => 'warning',
                    default => 'gray',
                }),
                TextColumn::make('tax_type')->label('Tax Type')->searchable(),
                TextColumn::make('tax_property')->label('Tax Property')->searchable(),
                TextColumn::make('description')->label('Description')->limit(60)->wrap(),
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
            ->recordUrl(fn (\App\Models\Asset $record) => route('filament.admin.resources.asset-years.index', ['owner' => $record->asset_owner_id, 'asset' => $record->id]))

            ->filters([
                \Filament\Tables\Filters\SelectFilter::make('asset_type')
                    ->label('Asset Type')
                    ->options(fn () => \App\Models\AssetType::query()->active()->ordered()->pluck('name', 'type')->all())
                    ->multiple()
                    ->preload(),

                \Filament\Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Status')
                    ->placeholder('All')
                    ->trueLabel('Active only')
                    ->falseLabel('Inactive only'),

                // Advanced filters (group, tax type, capabilities)
                \Filament\Tables\Filters\Filter::make('advanced')
                    ->label('More filters')
                    ->form([
                        \Filament\Forms\Components\Select::make('group')
                            ->label('Group')
                            ->options(\App\Models\Asset::GROUPS),
                        \Filament\Forms\Components\Select::make('tax_type')
                            ->label('Tax Type')
                            ->options(\App\Models\Asset::TAX_TYPES),
                        \Filament\Forms\Components\Checkbox::make('cap_income')->label('Gen. Income'),
                        \Filament\Forms\Components\Checkbox::make('cap_expenses')->label('Gen. Expenses'),
                        \Filament\Forms\Components\Checkbox::make('cap_mortgage')->label('Mortgage'),
                        \Filament\Forms\Components\Checkbox::make('cap_market_value')->label('Market Value'),
                    ])
                    ->query(function ($query, array $data) {
                        if (! empty($data['group'])) {
                            $query->where('group', $data['group']);
                        }
                        if (! empty($data['tax_type'])) {
                            $query->where('tax_type', $data['tax_type']);
                        }
                        if ($data['cap_income'] !== null && $data['cap_income'] !== '') {
                            $codes = \App\Models\AssetType::query()
                                ->where('can_generate_income', (bool) $data['cap_income'])
                                ->pluck('type');
                            $query->whereIn('asset_type', $codes);
                        }
                        if ($data['cap_expenses'] !== null && $data['cap_expenses'] !== '') {
                            $codes = \App\Models\AssetType::query()
                                ->where('can_generate_expenses', (bool) $data['cap_expenses'])
                                ->pluck('type');
                            $query->whereIn('asset_type', $codes);
                        }
                        if ($data['cap_mortgage'] !== null && $data['cap_mortgage'] !== '') {
                            $codes = \App\Models\AssetType::query()
                                ->where('can_have_mortgage', (bool) $data['cap_mortgage'])
                                ->pluck('type');
                            $query->whereIn('asset_type', $codes);
                        }
                        if ($data['cap_market_value'] !== null && $data['cap_market_value'] !== '') {
                            $codes = \App\Models\AssetType::query()
                                ->where('can_have_market_value', (bool) $data['cap_market_value'])
                                ->pluck('type');
                            $query->whereIn('asset_type', $codes);
                        }

                        return $query;
                    }),

            ])

            ->toolbarActions([]);
    }
}
