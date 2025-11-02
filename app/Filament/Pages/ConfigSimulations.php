<?php

namespace App\Filament\Pages;

use App\Filament\Resources\SimulationConfigurations\Tables\SimulationConfigurationsTable;
use App\Models\AssetConfiguration;
use App\Models\SimulationConfiguration;
use App\Services\CurrentAssetConfiguration;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Pages\Page;
use Filament\Panel;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Attributes\Locked;

class ConfigSimulations extends Page implements HasTable
{
    use InteractsWithTable;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-calculator';

    protected static ?string $navigationLabel = 'Simulations';

    protected static ?string $title = 'Simulations';

    protected static string $routePath = '/config/{configuration}/simulations';

    #[Locked]
    public ?AssetConfiguration $record = null;

    public function mount(): void
    {
        $configurationId = request()->route('configuration');
        if (! $configurationId) {
            abort(404);
        }

        $this->record = AssetConfiguration::findOrFail($configurationId);
        app(CurrentAssetConfiguration::class)->set($this->record);
    }

    public static function getRouteName(?Panel $panel = null): string
    {
        return 'filament.admin.pages.config-simulations';
    }

    public static function shouldRegisterNavigation(): bool
    {
        return false;
    }

    public static function canAccess(): bool
    {
        return true;
    }

    /**
     * @return Builder<SimulationConfiguration>
     */
    protected function getTableQuery(): Builder
    {
        if (! $this->record) {
            return SimulationConfiguration::query()->whereRaw('1 = 0');
        }

        return SimulationConfiguration::query()
            ->where('asset_configuration_id', $this->record->id)
            ->with(['assetConfiguration']);
    }

    public function table(Table $table): Table
    {
        return SimulationConfigurationsTable::configure($table)
            ->query($this->getTableQuery())
            ->filtersLayout(FiltersLayout::AboveContent)
            ->paginated([50, 100, 150])
            ->defaultPaginationPageOption(50)
            ->paginationPageOptions([50, 100, 150]);
    }

    public function getTitle(): string
    {
        return $this->record ? ('Simulations - '.$this->record->name) : 'Simulations';
    }

    public function getHeading(): string
    {
        return $this->getTitle();
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('compare_simulations')
                ->label('Compare Simulations')
                ->icon('heroicon-o-arrows-right-left')
                ->color('primary')
                ->form([
                    Select::make('simulationA')
                        ->label('Simulation A (Baseline)')
                        ->options(function () {
                            if (! $this->record) {
                                return [];
                            }

                            return SimulationConfiguration::where('user_id', auth()->id())
                                ->where('asset_configuration_id', $this->record->id)
                                ->pluck('name', 'id');
                        })
                        ->searchable()
                        ->required(),

                    Select::make('simulationB')
                        ->label('Simulation B (Scenario)')
                        ->options(function () {
                            if (! $this->record) {
                                return [];
                            }

                            return SimulationConfiguration::where('user_id', auth()->id())
                                ->where('asset_configuration_id', $this->record->id)
                                ->pluck('name', 'id');
                        })
                        ->searchable()
                        ->required(),
                ])
                ->action(function (array $data): void {
                    $this->redirect(route('filament.admin.pages.compare-dashboard', [
                        'configuration' => $this->record->id,
                        'simulationA' => $data['simulationA'],
                        'simulationB' => $data['simulationB'],
                    ]));
                }),
        ];
    }
}
