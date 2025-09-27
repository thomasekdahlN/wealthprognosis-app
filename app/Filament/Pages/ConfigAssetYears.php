<?php

namespace App\Filament\Pages;

use App\Filament\Resources\AssetYears\Tables\AssetYearsTable;
use App\Models\Asset;
use App\Models\AssetConfiguration;
use App\Models\AssetYear;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Panel;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Database\Eloquent\Builder;

class ConfigAssetYears extends Page implements HasTable
{
    use InteractsWithTable;

    protected static bool $shouldRegisterNavigation = false;

    protected static string $routePath = '/config/{configuration}/assets/{asset}/years';

    protected string $view = 'filament.pages.config-asset-years';

    public ?AssetConfiguration $configuration = null;

    public ?Asset $asset = null;

    public function mount(): void
    {
        $configurationParam = request()->route('configuration') ?? request()->query('configuration');
        $assetParam = request()->route('asset') ?? request()->query('asset');

        $configurationId = $configurationParam instanceof AssetConfiguration
            ? $configurationParam->getKey()
            : (int) ($configurationParam ?? 0);

        $assetId = $assetParam instanceof Asset
            ? $assetParam->getKey()
            : (int) ($assetParam ?? 0);

        if ($configurationId <= 0 || $assetId <= 0) {
            abort(404);
        }

        $this->configuration = $configurationParam instanceof AssetConfiguration
            ? $configurationParam
            : AssetConfiguration::find($configurationId);

        $this->asset = $assetParam instanceof Asset
            ? $assetParam
            : Asset::find($assetId);

    }

    public function getTitle(): string|Htmlable
    {
        if ($this->asset) {
            $type = method_exists($this->asset, 'getTypeLabel') ? $this->asset->getTypeLabel() : '';

            return ($this->asset->name ?? ('Asset #'.$this->asset->id)).($type ? ' ('.$type.')' : '');
        }

        return 'Asset Years';
    }

    protected function getHeaderActions(): array
    {
        $configurationId = $this->configuration?->getKey() ?? 0;
        $assetId = $this->asset?->getKey() ?? 0;

        return [
            Action::make('new_year')
                ->label('New year')
                ->icon('heroicon-m-plus')
                ->color('primary')
                ->action(function () use ($assetId, $configurationId) {
                    if ($assetId <= 0 || $configurationId <= 0) {
                        Notification::make()->title('Missing context')->body('This page requires both configuration and asset context.')->danger()->send();

                        return null;
                    }

                    $asset = Asset::query()->find($assetId);
                    if (! $asset) {
                        Notification::make()->title('Asset not found')->body('Asset ID: '.$assetId)->danger()->send();

                        return null;
                    }

                    $maxYear = AssetYear::query()->where('asset_id', $assetId)->max('year');
                    $nextYear = $maxYear ? ((int) $maxYear + 1) : (int) date('Y');

                    $record = new AssetYear;
                    $record->year = $nextYear;
                    $record->asset()->associate($asset);
                    $record->assetConfiguration()->associate($configurationId);
                    $record->save();

                    Notification::make()->title('Year '.$nextYear.' added')->success()->send();

                    // Refresh the table without full page reload
                    $this->dispatch('refresh');

                    return null;
                }),
        ];
    }

    public function table(Table $table): Table
    {
        return AssetYearsTable::configure($table)
            ->query($this->getTableQuery());
    }

    protected function getTableQuery(): Builder
    {
        // Prefer stored context during Livewire updates, fall back to route/query for initial GET.
        $configurationId = (int) ($this->configuration?->getKey()
            ?? (request()->route('configuration') instanceof AssetConfiguration
                ? request()->route('configuration')->getKey()
                : (int) (request()->route('configuration') ?? request()->query('configuration') ?? 0)));

        $assetId = (int) ($this->asset?->getKey()
            ?? (request()->route('asset') instanceof Asset
                ? request()->route('asset')->getKey()
                : (int) (request()->route('asset') ?? request()->query('asset') ?? 0)));

        if ($configurationId <= 0 || $assetId <= 0) {
            abort(404);
        }

        return AssetYear::query()
            ->where('asset_configuration_id', $configurationId)
            ->where('asset_id', $assetId);
    }

    public function getBreadcrumbs(): array
    {
        $configurationId = (int) ($this->configuration?->getKey()
            ?? (request()->route('configuration') instanceof AssetConfiguration
                ? request()->route('configuration')->getKey()
                : (int) (request()->route('configuration') ?? request()->query('configuration') ?? 0)));

        $assetId = (int) ($this->asset?->getKey()
            ?? (request()->route('asset') instanceof Asset
                ? request()->route('asset')->getKey()
                : (int) (request()->route('asset') ?? request()->query('asset') ?? 0)));

        $crumbs = [];
        $crumbs[\App\Filament\Resources\AssetConfigurations\AssetConfigurationResource::getUrl('index')] = __('Configurations');

        if ($configurationId) {
            $configuration = AssetConfiguration::find($configurationId);
            $crumbs[\App\Filament\Resources\AssetConfigurations\AssetConfigurationResource::getUrl('assets', ['record' => $configurationId])] = $configuration?->name ?? (__('Configuration').' #'.$configurationId);
        }

        if ($assetId) {
            $asset = Asset::find($assetId);
            $crumbs[] = $asset?->name ?? (__('Asset').' #'.$assetId);
        }

        return $crumbs;
    }

    public static function getRoutes(): array
    {
        // Register this page directly at the pretty URL under the admin panel
        return [
            '/config/{configuration}/assets/{asset}/years' => static::class,
        ];
    }

    public static function getRouteName(?Panel $panel = null): string
    {
        return 'filament.admin.pages.config-asset-years.pretty';
    }
}
