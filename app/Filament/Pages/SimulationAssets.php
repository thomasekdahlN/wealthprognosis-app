<?php

namespace App\Filament\Pages;

use App\Models\SimulationAsset;
use App\Models\SimulationConfiguration;
use Filament\Pages\Page;
use Filament\Tables\Table;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Actions\Action;
use Filament\Support\Exceptions\Halt;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Database\Eloquent\Builder;

class SimulationAssets extends Page implements HasTable
{
    use InteractsWithTable;

    protected string $view = 'filament.pages.simulation-assets';

    protected static bool $shouldRegisterNavigation = false;

    public ?SimulationConfiguration $simulationConfiguration = null;

    public function mount(): void
    {
        $simulationConfigurationId = request()->query('simulation_configuration_id') ?? request()->route('record');

        if (!$simulationConfigurationId) {
            throw new Halt(404);
        }

        $this->simulationConfiguration = SimulationConfiguration::with([
            'assetConfiguration',
            'simulationAssets.simulationAssetYears' => function ($query) {
                $query->orderBy('year');
            }
        ])->find($simulationConfigurationId);

        if (!$this->simulationConfiguration) {
            throw new Halt(404);
        }

        // Check if user has access to this simulation
        if ($this->simulationConfiguration->user_id !== auth()->id()) {
            throw new Halt(403);
        }
    }

    public function getTitle(): string|Htmlable
    {
        return $this->simulationConfiguration
            ? "Simulation Assets - {$this->simulationConfiguration->name}"
            : 'Simulation Assets';
    }

    public function getHeading(): string|Htmlable
    {
        return $this->simulationConfiguration
            ? "Assets in {$this->simulationConfiguration->name}"
            : 'Simulation Assets';
    }

    public function getSubheading(): string|Htmlable|null
    {
        if (!$this->simulationConfiguration) {
            return null;
        }

        $config = $this->simulationConfiguration->assetConfiguration;
        $assetsCount = $this->simulationConfiguration->simulationAssets()->count();

        return "Based on {$config->name} • {$assetsCount} assets • Created " . $this->simulationConfiguration->created_at->diffForHumans();
    }

    public function table(Table $table): Table
    {
        return $table
            ->query($this->getTableQuery())
            ->columns([
                IconColumn::make('assetType.icon')
                    ->label('')
                    ->icon(fn (?string $state): string => $state && \App\Helpers\HeroiconValidator::isValid($state) ? $state : 'heroicon-o-cube')
                    ->size('lg')
                    ->color(fn ($record): string => $record->assetType?->color ?: 'gray')
                    ->width('60px')
                    ->tooltip(fn ($record): ?string => $record->assetType?->name ?? 'Asset Type'),

                TextColumn::make('name')
                    ->label('Asset Name')
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->description(fn (SimulationAsset $record): string => $record->description ?? ''),

                TextColumn::make('asset_type')
                    ->label('Type')
                    ->badge()
                    ->color('primary')
                    ->formatStateUsing(fn (string $state): string => ucfirst($state))
                    ->sortable(),

                TextColumn::make('group')
                    ->label('Group')
                    ->badge()
                    ->color('success')
                    ->formatStateUsing(fn (string $state): string => ucfirst($state))
                    ->sortable(),

                TextColumn::make('tax_type')
                    ->label('Tax Type')
                    ->formatStateUsing(fn (string $state): string => ucfirst($state))
                    ->sortable()
                    ->toggleable(),



                TextColumn::make('sort_order')
                    ->label('Order')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('asset_type')
                    ->label('Asset Type')
                    ->options(function () {
                        if (!$this->simulationConfiguration) {
                            return [];
                        }
                        return $this->simulationConfiguration->simulationAssets()
                            ->distinct()
                            ->pluck('asset_type', 'asset_type')
                            ->mapWithKeys(fn ($value, $key) => [$key => ucfirst($value)])
                            ->toArray();
                    })
                    ->multiple(),

                SelectFilter::make('group')
                    ->label('Group')
                    ->options([
                        'private' => 'Private',
                        'company' => 'Company',
                    ])
                    ->multiple(),

                SelectFilter::make('tax_type')
                    ->label('Tax Type')
                    ->options(function () {
                        if (!$this->simulationConfiguration) {
                            return [];
                        }
                        return $this->simulationConfiguration->simulationAssets()
                            ->distinct()
                            ->pluck('tax_type', 'tax_type')
                            ->mapWithKeys(fn ($value, $key) => [$key => ucfirst($value)])
                            ->toArray();
                    })
                    ->multiple(),
            ])

            ->defaultSort('sort_order')
            ->paginated([50, 100, 150])
            ->defaultPaginationPageOption(50)
            ->paginationPageOptions([50, 100, 150])
            ->recordUrl(fn (SimulationAsset $record): string =>
                route('filament.admin.pages.simulation-asset-years', [
                    'simulation_configuration' => $this->simulationConfiguration->id,
                    'asset' => $record->id
                ])
            );
    }

    protected function getTableQuery(): Builder
    {
        if (!$this->simulationConfiguration) {
            return SimulationAsset::query()->whereRaw('1 = 0'); // Empty query
        }

        return SimulationAsset::query()
            ->where('asset_configuration_id', $this->simulationConfiguration->id)
            ->with(['simulationAssetYears', 'assetType'])
            ->orderBy('sort_order')
            ->orderBy('name');
    }

    protected function getHeaderActions(): array
    {
        if (!$this->simulationConfiguration) {
            return [];
        }

        return [
            Action::make('dashboard')
                ->label('Dashboard')
                ->icon('heroicon-o-chart-bar')
                ->color('primary')
                ->url(route('filament.admin.pages.simulation-dashboard', [
                    'simulation_configuration_id' => $this->simulationConfiguration->id
                ])),

            Action::make('export_excel')
                ->label('Export Excel')
                ->icon('heroicon-o-document-arrow-down')
                ->color('success')
                ->action(function () {
                    $path = \App\Services\SimulationExportService::export($this->simulationConfiguration);
                    $this->dispatch('download-file', url: route('download.analysis', ['file' => basename($path)]), filename: basename($path));
                }),

            Action::make('back_to_simulations')
                ->label('Back to Simulations')
                ->icon('heroicon-o-arrow-left')
                ->color('gray')
                ->url(route('filament.admin.resources.simulation-configurations.index')),
        ];
    }

    public function getBreadcrumbs(): array
    {
        $breadcrumbs = [];

        $breadcrumbs[route('filament.admin.resources.simulation-configurations.index')] = 'Simulations';

        if ($this->simulationConfiguration) {
            $breadcrumbs[route('filament.admin.pages.simulation-dashboard', [
                'simulation_configuration_id' => $this->simulationConfiguration->id
            ])] = $this->simulationConfiguration->name;
        }

        $breadcrumbs[] = 'Assets'; // Current page (no URL)

        return $breadcrumbs;
    }

    protected function getViewData(): array
    {
        return [
            'simulationConfiguration' => $this->simulationConfiguration,
        ];
    }

    public static function getRoutes(): array
    {
        return [
            '/simulation-assets' => static::class,
        ];
    }
}
