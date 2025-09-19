<?php

namespace App\Filament\Resources\Events;

use App\Filament\Resources\Events\Pages\ListEvents;
use App\Filament\Resources\Events\Tables\EventsTable;
use App\Models\Asset;
use App\Services\CurrentAssetConfiguration;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class EventResource extends Resource
{
    protected static ?string $model = Asset::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCalendarDays;

    protected static ?string $navigationLabel = 'Events';

    protected static ?int $navigationSort = 2;

    protected static ?string $maxContentWidth = 'full';

    public static function getEloquentQuery(): \Illuminate\Database\Eloquent\Builder
    {
        $currentYear = (int) date('Y');
        $activeAssetConfigurationId = app(\App\Services\CurrentAssetConfiguration::class)->id();

        $query = parent::getEloquentQuery()
            ->with(['configuration', 'assetType'])
            ->whereHas('years', function ($query) use ($currentYear) {
                $query->where('year', '>', $currentYear);
            });

        // Filter by active asset configuration if one is selected
        if ($activeAssetConfigurationId) {
            $query->where('assets.asset_configuration_id', $activeAssetConfigurationId);
        }

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

        $query = static::getModel()::query()
            ->whereHas('years', function ($q) use ($currentYear) {
                $q->where('year', '>', $currentYear);
            });

        if ($activeAssetConfigurationId) {
            $query->where('asset_configuration_id', $activeAssetConfigurationId);
        }

        return (string) $query->count();
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'warning';
    }
}
