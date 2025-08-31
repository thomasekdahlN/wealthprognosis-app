<?php

namespace App\Filament\Resources\AssetConfigurations\Tables;

use App\Filament\Resources\AssetConfigurations\Actions\RunSimulationAction;
use Filament\Actions\Action;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Table;

class AssetConfigurationsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                IconColumn::make('icon')
                    ->label('')
                    ->icon(fn (?string $state): string => $state && \App\Helpers\HeroiconValidator::isValid($state) ? $state : 'heroicon-o-user')
                    ->size('lg')
                    ->color(fn ($record): string => $record->color ?: 'gray')
                    ->width('60px')
                    ->tooltip(fn ($record): ?string => $record->icon ? "Icon: {$record->icon}" : 'Default user icon'),
                TextColumn::make('name')
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->description(fn ($record) => $record->description)
                    ->wrap(),
                TextColumn::make('birth_year')
                    ->label('Birth Year')
                    ->sortable()
                    ->alignStart()
                    ->formatStateUsing(fn (?int $state): string => $state ? (string) $state : ''),
                TextColumn::make('pension_official_age')
                    ->label('Pension Official Age')
                    ->numeric(locale: \App\Helpers\AmountHelper::getNorwegianLocale())
                    ->sortable()
                    ->alignEnd(),
                TextColumn::make('pension_wish_age')
                    ->label('Pension Wish Age')
                    ->numeric(locale: \App\Helpers\AmountHelper::getNorwegianLocale())
                    ->sortable()
                    ->alignEnd(),
                TextColumn::make('tags')
                    ->label('Tags')
                    ->badge()
                    ->separator(',')
                    ->color('primary')
                    ->limit(3)
                    ->tooltip(function (TextColumn $column): ?string {
                        $state = $column->getState();
                        if (is_array($state) && count($state) > 3) {
                            return 'All tags: '.implode(', ', $state);
                        }

                        return null;
                    }),
                TextColumn::make('updated_at')
                    ->label('Last Updated')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(),
            ])
            ->filtersLayout(FiltersLayout::AboveContent)
            ->filters([])
            ->toggleColumnsTriggerAction(fn ($action) => $action->modalHeading('Choose columns'))
            ->actions([
                RunSimulationAction::make(),
                Action::make('dashboard')
                    ->label('Dashboard')
                    ->icon('heroicon-o-chart-bar')
                    ->color('primary')
                    ->url(fn (\App\Models\AssetConfiguration $record) => route('filament.admin.pages.dashboard', ['asset_owner_id' => $record->id]))
                    ->openUrlInNewTab(false),
            ])
            ->defaultSort('name')
            ->paginated([50, 100, 150])
            ->defaultPaginationPageOption(50)
            ->paginationPageOptions([50, 100, 150])
            ->recordUrl(fn (\App\Models\AssetConfiguration $record) => \App\Filament\Resources\AssetConfigurations\AssetConfigurationResource::getUrl('assets', ['record' => $record->getKey()]));
    }
}
