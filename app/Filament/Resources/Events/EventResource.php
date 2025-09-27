<?php

namespace App\Filament\Resources\Events;

use App\Filament\Resources\Events\Pages\ListEvents;
use App\Filament\Resources\Events\Tables\EventsTable;
use App\Models\AssetYear;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class EventResource extends Resource
{
    protected static ?string $model = AssetYear::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCalendarDays;

    protected static ?string $navigationLabel = 'Events';

    protected static ?int $navigationSort = 2;

    protected static ?string $maxContentWidth = 'full';

    public static function getEloquentQuery(): \Illuminate\Database\Eloquent\Builder
    {
        $currentYear = (int) date('Y');
        $activeAssetConfigurationId = app(\App\Services\CurrentAssetConfiguration::class)->id();

        $query = parent::getEloquentQuery()
            ->with(['asset', 'asset.assetType'])
            ->where('year', '>', $currentYear);

        $query->where('asset_configuration_id', $activeAssetConfigurationId ?? -1);

        return $query;
    }

    public static function table(Table $table): Table
    {
        return EventsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListEvents::route('/'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        // Count distinct assets with future years
        $currentYear = (int) date('Y');
        $activeAssetConfigurationId = app(\App\Services\CurrentAssetConfiguration::class)->id();

        if (! $activeAssetConfigurationId) {
            return '0';
        }

        $query = static::getModel()::query()
            ->where('year', '>', $currentYear)
            ->where('asset_configuration_id', $activeAssetConfigurationId);

        return (string) $query->count();
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'warning';
    }
}
