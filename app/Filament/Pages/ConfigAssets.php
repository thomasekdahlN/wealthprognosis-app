<?php

namespace App\Filament\Pages;

use App\Models\Asset;
use App\Models\AssetConfiguration;
use App\Services\AssetConfigurationSessionService;
use BackedEnum;
use Filament\Pages\Page;
use Filament\Panel;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Attributes\Locked;

class ConfigAssets extends Page implements HasTable
{
    use InteractsWithTable;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-rectangle-stack';

    protected string $view = 'filament.pages.config-assets';

    protected static ?string $navigationLabel = 'Assets';

    protected static ?string $title = 'Assets';

    #[Locked]
    public ?AssetConfiguration $record = null;

    public function mount(): void
    {
        $recordId = request()->route('record');

        if ($recordId) {
            $this->record = AssetConfiguration::findOrFail($recordId);
            AssetConfigurationSessionService::setActiveAssetOwner($this->record);
        } else {
            $this->record = AssetConfigurationSessionService::getActiveAssetOwner();
        }

        if (!$this->record) {
            // Redirect to configurations if no asset configuration is found
            redirect()->route('filament.admin.resources.asset-configurations.index');
        }
    }

    public static function getRouteName(?Panel $panel = null): string
    {
        return 'filament.admin.pages.config-assets';
    }

    public static function getRouteKeyName(): ?string
    {
        return 'record';
    }

    public static function shouldRegisterNavigation(): bool
    {
        return false;
    }

    public static function canAccess(): bool
    {
        return true;
    }

    protected function getTableQuery(): Builder
    {
        if (!$this->record) {
            return Asset::query()->whereRaw('1 = 0'); // Empty result
        }

        return Asset::query()
            ->where('asset_configuration_id', $this->record->id)
            ->with(['configuration', 'assetType']);
    }

    public function table(Table $table): Table
    {
        return $table
            ->query($this->getTableQuery())
            ->columns([
                TextColumn::make('sort_order')
                    ->label('Sort Order')
                    ->alignLeft()
                    ->sortable(),
                TextColumn::make('name')
                    ->label('Name')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),
                TextColumn::make('asset_type')
                    ->label('Type')
                    ->searchable()
                    ->sortable()
                    ->badge()
                    ->color('primary'),
                TextColumn::make('assetType.name')
                    ->label('Asset Type')
                    ->sortable(),
                TextColumn::make('tax_type')
                    ->label('Tax Type')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('tax_property')
                    ->label('Tax Property')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('description')
                    ->label('Description')
                    ->limit(60)
                    ->wrap(),
                IconColumn::make('is_active')
                    ->label('Active')
                    ->boolean(),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('asset_type')
                    ->label('Asset Type')
                    ->options(fn () => \App\Models\AssetType::query()->active()->ordered()->pluck('name', 'type')->all())
                    ->multiple()
                    ->preload(),
                TernaryFilter::make('is_active')
                    ->label('Status')
                    ->placeholder('All')
                    ->trueLabel('Active only')
                    ->falseLabel('Inactive only'),
            ])
            ->filtersLayout(FiltersLayout::AboveContent)
            ->actions([
                // Add actions if needed
            ])
            ->defaultSort('sort_order')
            ->paginated([50, 100, 150])
            ->defaultPaginationPageOption(50)
            ->paginationPageOptions([50, 100, 150]);
    }

    public function getTitle(): string
    {
        if ($this->record) {
            return 'Assets - ' . $this->record->name;
        }

        return 'Assets';
    }

    public function getHeading(): string
    {
        return $this->getTitle();
    }
}
