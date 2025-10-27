<?php

namespace App\Filament\Pages;

use App\Filament\Resources\Events\Tables\EventsTable;
use App\Models\Asset;
use App\Models\AssetConfiguration;
use App\Models\AssetYear;
use App\Services\CurrentAssetConfiguration;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Panel;
use Filament\Schemas\Components\Grid;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Arr;
use Livewire\Attributes\Locked;

class ConfigEvents extends Page implements HasTable
{
    use InteractsWithTable;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-calendar-days';

    protected static ?string $navigationLabel = 'Events';

    protected string $view = 'filament.pages.config-events';

    protected static ?string $title = 'Events';

    protected static string $routePath = '/config/{configuration}/events';

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
        return 'filament.admin.pages.config-events';
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
     * @return Builder<AssetYear>
     */
    protected function getTableQuery(): Builder
    {
        $currentYear = (int) date('Y');

        if (! $this->record) {
            return AssetYear::query()->whereRaw('1 = 0');
        }

        return AssetYear::query()
            ->with(['asset', 'asset.assetType'])
            ->where('year', '>', $currentYear)
            ->where('asset_configuration_id', $this->record->id);
    }

    public function table(Table $table): Table
    {
        return EventsTable::configure($table)
            ->query($this->getTableQuery())
            ->filtersLayout(FiltersLayout::AboveContent)
            ->paginated([50, 100, 150])
            ->defaultPaginationPageOption(50)
            ->paginationPageOptions([50, 100, 150]);
    }

    protected function getHeaderActions(): array
    {
        // Build asset options for the current configuration
        $assetOptions = [];
        if ($this->record) {
            $assetOptions = Asset::query()
                ->where('asset_configuration_id', $this->record->id)
                ->orderBy('name')
                ->pluck('name', 'id')
                ->all();
        }

        return [
            Action::make('createEvent')
                ->label('New Event')
                ->icon('heroicon-o-calendar-days')
                ->form([
                    Grid::make(2)->schema([
                        Select::make('asset_id')
                            ->label('Choose Existing Asset')
                            ->options($assetOptions)
                            ->searchable()
                            ->preload()
                            ->native(false)
                            ->disabled(fn (\Filament\Schemas\Components\Utilities\Get $get): bool => (bool) $get('create_new_asset'))
                            ->columnSpan(2)
                            ->helperText('Select an existing asset from the current configuration or toggle to create a new one.'),
                        Toggle::make('create_new_asset')
                            ->label('Create new asset instead')
                            ->default(false)
                            ->live(),
                        TextInput::make('year')
                            ->label('Event Year')
                            ->numeric()
                            ->default((int) date('Y') + 1)
                            ->required(),
                    ]),
                    Grid::make(3)->schema([
                        TextInput::make('new_asset_name')
                            ->label('New Asset Name')
                            ->disabled(fn (\Filament\Schemas\Components\Utilities\Get $get): bool => ! (bool) $get('create_new_asset'))
                            ->required(fn (\Filament\Schemas\Components\Utilities\Get $get): bool => (bool) $get('create_new_asset'))
                            ->maxLength(255),
                        Select::make('new_asset_type')
                            ->label('New Asset Type')
                            ->options(\App\Models\AssetType::query()->active()->ordered()->pluck('name', 'type'))
                            ->disabled(fn (\Filament\Schemas\Components\Utilities\Get $get): bool => ! (bool) $get('create_new_asset'))
                            ->required(fn (\Filament\Schemas\Components\Utilities\Get $get): bool => (bool) $get('create_new_asset'))
                            ->searchable()
                            ->preload()
                            ->native(false),
                        Select::make('new_asset_group')
                            ->label('Group')
                            ->options(Asset::GROUPS)
                            ->default('private')
                            ->disabled(fn (\Filament\Schemas\Components\Utilities\Get $get): bool => ! (bool) $get('create_new_asset'))
                            ->required(fn (\Filament\Schemas\Components\Utilities\Get $get): bool => (bool) $get('create_new_asset')),
                    ]),
                    TextInput::make('description')
                        ->label('Description')
                        ->placeholder('Short description of the event (optional)')
                        ->maxLength(65535)
                        ->columnSpanFull(),
                ])
                ->action(function (array $data) {
                    if (! $this->record) {
                        Notification::make()->title('No active asset configuration selected. Choose a configuration first.')->danger()->send();

                        return null;
                    }

                    $year = (int) ($data['year'] ?? 0);
                    if ($year <= (int) date('Y')) {
                        Notification::make()->title('Event year must be in the future (greater than the current year).')->danger()->send();

                        return null;
                    }

                    $description = (string) ($data['description'] ?? '');

                    // Determine or create asset
                    if (Arr::get($data, 'create_new_asset')) {
                        $maxSort = Asset::where('asset_configuration_id', $this->record->id)->max('sort_order') ?? 0;
                        $asset = new Asset([
                            'asset_configuration_id' => $this->record->id,
                            'name' => (string) $data['new_asset_name'],
                            'description' => '',
                            'asset_type' => (string) $data['new_asset_type'],
                            'group' => (string) ($data['new_asset_group'] ?? 'private'),
                            'tax_type' => null,
                            'tax_property' => null,
                            'tax_country' => 'no',
                            'is_active' => true,
                            'sort_order' => $maxSort + 1,
                        ]);
                        $asset->save();
                    } else {
                        $assetId = (int) ($data['asset_id'] ?? 0);
                        $asset = Asset::query()
                            ->where('asset_configuration_id', $this->record->id)
                            ->find($assetId);
                        if (! $asset) {
                            Notification::make()->title('Please choose an existing asset or create a new one.')->danger()->send();

                            return null;
                        }
                    }

                    $assetYear = AssetYear::firstOrCreate(
                        [
                            'asset_id' => $asset->id,
                            'year' => $year,
                        ],
                        [
                            'asset_configuration_id' => $this->record->id,
                            'description' => $description,
                            'income_changerate' => optional($asset->assetType)->income_changerate,
                            'expence_changerate' => optional($asset->assetType)->expence_changerate,
                            'asset_changerate' => optional($asset->assetType)->asset_changerate,
                        ]
                    );

                    if (empty($assetYear->asset_configuration_id)) {
                        $assetYear->asset_configuration_id = $this->record->id;
                    }
                    if ($description !== '') {
                        $assetYear->description = $description;
                    }
                    $assetYear->save();

                    Notification::make()
                        ->title('Event saved for '.$year.' (Asset #'.$asset->id.').')
                        ->success()
                        ->send();

                    $this->dispatch('refresh');

                    return null;
                })
                ->modalHeading('Create Event')
                ->modalSubmitActionLabel('Continue')
                ->modalCancelActionLabel('Cancel')
                ->stickyModalHeader()
                ->stickyModalFooter(),
        ];
    }

    public function getTitle(): string
    {
        return $this->record ? ('Events - '.$this->record->name) : 'Events';
    }

    public function getHeading(): string
    {
        return $this->getTitle();
    }
}
