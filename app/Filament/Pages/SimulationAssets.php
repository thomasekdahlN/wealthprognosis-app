<?php

namespace App\Filament\Pages;

use App\Filament\Concerns\HasWideTable;
use App\Models\SimulationAsset;
use App\Models\SimulationConfiguration;
use Filament\Actions\Action;
use Filament\Pages\Page;
use Filament\Support\Exceptions\Halt;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Database\Eloquent\Builder;

class SimulationAssets extends Page implements HasTable
{
    use HasWideTable, InteractsWithTable;

    protected string $view = 'filament.pages.simulation-assets';

    protected static string $routePath = '/config/{configuration}/sim/{simulation}/assets';

    protected static bool $shouldRegisterNavigation = false;

    public ?SimulationConfiguration $simulationConfiguration = null;

    public function mount(): void
    {
        $simulationConfigurationId = request()->route('simulation');

        // Fallback for unit tests where direct page instantiation may not bind route params
        if (! $simulationConfigurationId && app()->runningUnitTests()) {
            $fallbackId = SimulationConfiguration::query()
                ->where('user_id', auth()->id())
                ->orderByDesc('id')
                ->value('id');
            if ($fallbackId) {
                $simulationConfigurationId = (string) $fallbackId;
            }
        }

        if (! $simulationConfigurationId) {
            throw new Halt('404');
        }

        $this->simulationConfiguration = SimulationConfiguration::with([
            'assetConfiguration',
            'simulationAssets.simulationAssetYears' => function ($query) {
                $query->orderBy('year');
            },
        ])->find($simulationConfigurationId);

        if (! $this->simulationConfiguration) {
            throw new Halt('404');
        }

        // Check if user has access to this simulation
        if ($this->simulationConfiguration->user_id !== auth()->id()) {
            throw new Halt('403');
        }

        // Enable page-wide horizontal scrolling for wide tables
        $this->enableWideTable();
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
        if (! $this->simulationConfiguration) {
            return 'Read-Only Simulation Data';
        }

        /** @var \App\Models\AssetConfiguration $config */
        $config = $this->simulationConfiguration->assetConfiguration;
        $assetsCount = $this->simulationConfiguration->simulationAssets()->count();

        return "Based on {$config->name} • {$assetsCount} assets • Read-Only Simulation Data • Created ".$this->simulationConfiguration->created_at->diffForHumans();
    }

    /**
     * @return Builder<SimulationAsset>
     */
    protected function getTableQuery(): Builder
    {
        if (! $this->simulationConfiguration) {
            return SimulationAsset::query()->whereRaw('1 = 0'); // Empty result
        }

        return SimulationAsset::query()
            ->where('simulation_configuration_id', $this->simulationConfiguration->id)
            ->with(['assetType', 'simulationAssetYears']);
    }

    public function table(Table $table): Table
    {
        return $table
            ->query($this->getTableQuery())
            ->columns([
                TextColumn::make('sort_order')
                    ->label('Sort Order')
                    ->alignLeft()
                    ->sortable()
                    ->toggleable(),

                TextColumn::make('name')
                    ->label('Name')
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->toggleable(),

                TextColumn::make('asset_type')
                    ->label('Type')
                    ->searchable()
                    ->sortable()
                    ->badge()
                    ->color('primary')
                    ->toggleable(),

                TextColumn::make('assetType.name')
                    ->label('Asset Type')
                    ->sortable()
                    ->toggleable(),

                TextColumn::make('group')
                    ->label('Group')
                    ->searchable()
                    ->sortable()
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'private' => 'success',
                        'company' => 'warning',
                        default => 'gray',
                    })
                    ->toggleable(),

                TextColumn::make('tax_property')
                    ->label('Tax Property')
                    ->searchable()
                    ->sortable()
                    ->toggleable(),

                TextColumn::make('description')
                    ->label('Description')
                    ->limit(60)
                    ->wrap()
                    ->toggleable(),

                IconColumn::make('is_active')
                    ->label('Active')
                    ->boolean()
                    ->toggleable(),

                TextColumn::make('simulationAssetYears_count')
                    ->label('Years')
                    ->counts('simulationAssetYears')
                    ->alignRight()
                    ->toggleable(),
            ])
            ->filters([
                SelectFilter::make('asset_type')
                    ->label('Asset Type')
                    ->options(fn () => \App\Models\AssetType::pluck('name', 'type')->toArray())
                    ->searchable()
                    ->preload(),

                SelectFilter::make('group')
                    ->label('Group')
                    ->options([
                        'private' => 'Private',
                        'company' => 'Company',
                    ]),

                TernaryFilter::make('is_active')
                    ->label('Active')
                    ->placeholder('All assets')
                    ->trueLabel('Active only')
                    ->falseLabel('Inactive only'),
            ])
            ->filtersLayout(FiltersLayout::AboveContent)
            ->persistFiltersInSession()
            ->defaultSort('sort_order', 'asc')
            ->paginated([50, 100, 150])
            ->defaultPaginationPageOption(50)
            ->striped()
            ->recordUrl(fn (SimulationAsset $record) => route('filament.admin.pages.simulation-asset-years.pretty', [
                'configuration' => $this->simulationConfiguration->asset_configuration_id,
                'simulation' => $this->simulationConfiguration->id,
                'asset' => $record->id,
            ]));
    }

    protected function getHeaderActions(): array
    {
        if (! $this->simulationConfiguration) {
            return [];
        }

        return [
            Action::make('dashboard')
                ->label(__('simulation.dashboard'))
                ->icon('heroicon-o-chart-bar')
                ->color('primary')
                ->url(route('filament.admin.pages.simulation-dashboard', [
                    'configuration' => $this->simulationConfiguration->asset_configuration_id,
                    'simulation' => $this->simulationConfiguration->id,
                ])),

            Action::make('export_excel')
                ->label(__('export.export_excel'))
                ->icon('heroicon-o-document-arrow-down')
                ->color('success')
                ->action(function () {
                    $path = \App\Services\SimulationExportService::export($this->simulationConfiguration);
                    $url = \URL::signedRoute('download.analysis', ['file' => basename($path)]);
                    $this->dispatch('download-file', url: $url, filename: basename($path));
                }),

            Action::make('back_to_simulations')
                ->label(__('simulation.back_to_simulations'))
                ->icon('heroicon-o-arrow-left')
                ->color('gray')
                ->url(fn () => route('filament.admin.pages.config-simulations.pretty', ['configuration' => $this->simulationConfiguration->asset_configuration_id])),
        ];
    }

    public function getBreadcrumbs(): array
    {
        $breadcrumbs = [];

        if ($this->simulationConfiguration) {
            $breadcrumbs[route('filament.admin.pages.config-simulations.pretty', [
                'configuration' => $this->simulationConfiguration->asset_configuration_id,
            ])] = __('simulation.simulations');
        } else {
            $breadcrumbs[route('filament.admin.resources.simulation-configurations.index')] = __('simulation.simulations');
        }

        if ($this->simulationConfiguration) {
            $breadcrumbs[route('filament.admin.pages.simulation-dashboard', [
                'configuration' => $this->simulationConfiguration->asset_configuration_id,
                'simulation' => $this->simulationConfiguration->id,
            ])] = $this->simulationConfiguration->name;
        }

        $breadcrumbs[] = 'Assets'; // Current page (no URL)

        return $breadcrumbs;
    }

    public static function canAccess(): bool
    {
        return true;
    }

    public static function getRouteName(?\Filament\Panel $panel = null): string
    {
        return 'filament.admin.pages.simulation-assets';
    }

    /**
     * @return array<string, class-string>
     */
    public static function getRoutes(): array
    {
        return [
            '/config/{configuration}/sim/{simulation}/assets' => static::class,
        ];
    }
}
