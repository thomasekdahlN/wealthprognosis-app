<?php

namespace App\Filament\Pages;

use App\Models\PrognosisChangeRate as AssetChangeRate;
use Filament\Actions\Action;
use Filament\Pages\Page;
use Filament\Support\Enums\MaxWidth;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

class ChangeRateAssets extends Page implements HasTable
{
    use InteractsWithTable;

    protected static bool $shouldRegisterNavigation = false;

    protected string $view = 'filament.pages.change-rate-assets';

    public string $scenario;

    public function mount(Request $request): void
    {
        $this->scenario = $request->get('scenario', 'realistic');
    }

    public function getTitle(): string|Htmlable
    {
        $scenarioLabel = \App\Models\PrognosisType::where('code', $this->scenario)->value('label') ?? ucfirst($this->scenario);

        return "Prognosis Change Rates - {$scenarioLabel}";
    }

    public function getHeading(): string|Htmlable
    {
        $scenarioLabel = \App\Models\PrognosisType::where('code', $this->scenario)->value('label') ?? ucfirst($this->scenario);

        return "Select Asset Type - {$scenarioLabel}";
    }

    public function getSubheading(): string|Htmlable|null
    {
        return 'Choose an asset type to configure its change rates over time';
    }

    public function getMaxWidth(): MaxWidth
    {
        return MaxWidth::Full;
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('back_to_scenarios')
                ->label('← Back to Scenarios')
                ->icon('heroicon-m-arrow-left')
                ->color('gray')
                ->action(function () {
                    return redirect()->route('filament.admin.pages.prognosis-change-rates');
                }),
        ];
    }

    public function table(Table $table): Table
    {
        return $table
            ->query($this->getTableQuery())
            ->columns([
                TextColumn::make('type')
                    ->label('Asset Type')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => AssetChangeRate::ASSET_TYPES[$state] ?? ucfirst($state)
                    )
                    ->color('primary')
                    ->sortable()
                    ->searchable(),

                TextColumn::make('description')
                    ->label('Description')
                    ->formatStateUsing(fn (string $state): string => $this->getAssetDescription($state))
                    ->wrap()
                    ->limit(80),

                TextColumn::make('record_count')
                    ->label('Years Configured')
                    ->badge()
                    ->color('success')
                    ->alignEnd(),

                TextColumn::make('year_range')
                    ->label('Year Range')
                    ->badge()
                    ->color('gray')
                    ->alignEnd(),

                TextColumn::make('average_rate')
                    ->label('Avg. Rate (%)')
                    ->numeric(decimalPlaces: 2)
                    ->alignEnd(),
            ])
            ->filters([
                \Filament\Tables\Filters\SelectFilter::make('asset_type')
                    ->label('Asset Type')
                    ->options(\App\Models\PrognosisChangeRate::ASSET_TYPES)
                    ->multiple()
                    ->preload()
                    ->query(function (\Illuminate\Database\Eloquent\Builder $query, array $data): \Illuminate\Database\Eloquent\Builder {
                        $values = $data['values'] ?? null;
                        if (empty($values)) {
                            return $query;
                        }

                        return $query->whereIn('asset_type', $values);
                    }),
            ])
            ->filtersLayout(\Filament\Tables\Enums\FiltersLayout::AboveContent)
            ->recordUrl(function ($record) {
                return route('filament.admin.pages.change-rate-table', [
                    'scenario' => $this->scenario,
                    'asset' => $record->type,
                ]);
            })
            ->emptyStateHeading('No assets found')
            ->emptyStateDescription('No asset types found for this scenario.')
            ->emptyStateIcon('heroicon-o-building-library');
    }

    protected function getTableQuery(): Builder
    {
        return AssetChangeRate::query()
            ->selectRaw("
                ROW_NUMBER() OVER (ORDER BY asset_type) as id,
                asset_type as type,
                asset_type as description,
                COUNT(*) as record_count,
                CONCAT(MIN(year), ' - ', MAX(year)) as year_range,
                AVG(change_rate) as average_rate
            ")
            ->where('scenario_type', $this->scenario)
            ->groupBy('asset_type')
            ->orderBy('asset_type');
    }

    public function getTableRecordKey($record): string
    {
        return $record->id ?? $record->type ?? 'unknown';
    }

    public function getAssetTypes(): array
    {
        // Get all available asset types for this scenario
        $assetTypes = AssetChangeRate::where('scenario_type', $this->scenario)
            ->select('asset_type')
            ->distinct()
            ->orderBy('asset_type')
            ->pluck('asset_type')
            ->toArray();

        // Add descriptions and counts
        $assets = [];
        foreach ($assetTypes as $type) {
            $recordCount = AssetChangeRate::where('scenario_type', $this->scenario)
                ->where('asset_type', $type)
                ->count();

            $yearRange = AssetChangeRate::where('scenario_type', $this->scenario)
                ->where('asset_type', $type)
                ->selectRaw('MIN(year) as min_year, MAX(year) as max_year')
                ->first();

            $averageRate = AssetChangeRate::where('scenario_type', $this->scenario)
                ->where('asset_type', $type)
                ->avg('change_rate');

            $assets[] = [
                'type' => $type,
                'label' => AssetChangeRate::ASSET_TYPES[$type] ?? ucfirst($type),
                'description' => $this->getAssetDescription($type),
                'record_count' => $recordCount,
                'year_range' => $yearRange ? "{$yearRange->min_year} - {$yearRange->max_year}" : 'No data',
                'average_rate' => $averageRate ? number_format($averageRate, 2).'%' : 'N/A',
            ];
        }

        return $assets;
    }

    private function getAssetDescription(string $type): string
    {
        return match ($type) {
            'kpi' => 'Consumer Price Index - inflation rate',
            'crypto' => 'Cryptocurrency investments',
            'gold' => 'Gold and precious metals',
            'bondfund' => 'Bond funds and fixed income',
            'equityfund' => 'Equity funds and stock investments',
            'stock' => 'Individual stocks',
            'cash' => 'Cash and cash equivalents',
            'house' => 'Real estate and housing',
            'rental' => 'Rental property investments',
            'cabin' => 'Vacation homes and cabins',
            'car' => 'Vehicle depreciation',
            'boat' => 'Boat and marine assets',
            'interest' => 'Interest rates',
            'otp' => 'Occupational pension schemes',
            'ask' => 'Equity savings accounts',
            'pension' => 'Public pension adjustments',
            'fire' => 'FIRE calculation rates',
            default => 'Asset growth rate configuration',
        };
    }
}
