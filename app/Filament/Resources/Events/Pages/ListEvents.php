<?php

namespace App\Filament\Resources\Events\Pages;

use App\Filament\Resources\Events\EventResource;
use App\Models\Asset;
use App\Models\AssetYear;
use App\Services\CurrentAssetConfiguration;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Grid;
use Illuminate\Support\Arr;

class ListEvents extends ListRecords
{
    public function mount(): void
    {
        $activeId = app(\App\Services\CurrentAssetConfiguration::class)->id();
        if ($activeId) {
            $this->redirectRoute('filament.admin.pages.config-events.pretty', [
                'configuration' => $activeId,
            ]);
        }
    }

    protected static string $resource = EventResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('createEvent')
                ->label('New Event')
                ->icon('heroicon-o-calendar-days')
                ->form(function () {
                    $currentConfig = app(CurrentAssetConfiguration::class)->get();
                    $assetOptions = [];
                    if ($currentConfig) {
                        $assetOptions = Asset::query()
                            ->where('asset_configuration_id', $currentConfig->id)
                            ->orderBy('name')
                            ->pluck('name', 'id')
                            ->all();
                    }

                    return [
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
                    ];
                })
                ->action(function (array $data) {
                    $currentConfig = app(CurrentAssetConfiguration::class)->get();
                    if (! $currentConfig) {
                        Notification::make()->title('No active asset configuration selected. Choose a configuration first.')->danger()->send();

                        return null;
                    }

                    $year = (int) ($data['year'] ?? 0);
                    if ($year <= (int) date('Y')) {
                        Notification::make()->title('Event year must be in the future (greater than the current year).')->danger()->send();

                        return null;
                    }

                    // Description can be numeric; keep it as-is
                    $description = (string) ($data['description'] ?? '');

                    $asset = null;

                    if (Arr::get($data, 'create_new_asset')) {
                        // Create new asset with minimal required fields
                        $maxSort = Asset::where('asset_configuration_id', $currentConfig->id)->max('sort_order') ?? 0;
                        $asset = new Asset([
                            'asset_configuration_id' => $currentConfig->id,
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
                            ->where('asset_configuration_id', $currentConfig->id)
                            ->find($assetId);
                        if (! $asset) {
                            Notification::make()->title('Please choose an existing asset or create a new one.')->danger()->send();

                            return null;
                        }
                    }

                    // Ensure AssetYear exists for the chosen year
                    $assetYear = AssetYear::firstOrCreate(
                        [
                            'asset_id' => $asset->id,
                            'year' => $year,
                        ],
                        [
                            'asset_configuration_id' => $currentConfig->id,
                            'description' => $description,
                            // Prefill changerates from asset type defaults
                            'income_changerate' => optional($asset->assetType)->income_changerate,
                            'expence_changerate' => optional($asset->assetType)->expence_changerate,
                            'asset_changerate' => optional($asset->assetType)->asset_changerate,
                        ]
                    );

                    // If it already existed, ensure configuration is set and update description if provided
                    if (empty($assetYear->asset_configuration_id)) {
                        $assetYear->asset_configuration_id = $currentConfig->id;
                    }
                    if ($description !== '') {
                        $assetYear->description = $description;
                    }
                    $assetYear->save();

                    // Friendly success message with DB file path for verification
                    $dbPath = (string) config('database.connections.sqlite.database');
                    if ($dbPath !== '' && ! str_starts_with($dbPath, DIRECTORY_SEPARATOR)) {
                        $dbPath = base_path($dbPath);
                    }

                    Notification::make()
                        ->title('Event saved for '.$year.' (Asset #'.$asset->id.').')
                        ->body('Saved in: '.$dbPath)
                        ->success()
                        ->send();

                    return redirect()->route('filament.admin.pages.config-events.pretty', [
                        'configuration' => $currentConfig->id,
                    ]);
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
        return 'Events';
    }

    public function getHeading(): string
    {
        $assetConfiguration = app(CurrentAssetConfiguration::class)->get();
        if ($assetConfiguration) {
            return 'Future Asset Events - '.$assetConfiguration->name;
        }

        return 'Future Asset Events';
    }

    public function getSubheading(): ?string
    {
        return 'Manage anticipated financial events that impact your prognosis (e.g. kid(s) moving out, pension start, inheritance, debt free milestone, career change, sabbatical, major purchase, sale of property, or other life changes).';
    }
}
