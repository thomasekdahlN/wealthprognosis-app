<?php

namespace App\Filament\Resources\Assets\Pages;

use App\Filament\Resources\Assets\AssetResource;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListAssets extends ListRecords
{
    protected static string $resource = AssetResource::class;

    protected function getTableQuery(): \Illuminate\Database\Eloquent\Builder
    {
        $activeId = (int) (app(\App\Services\CurrentAssetConfiguration::class)->id() ?? 0);
        $query = \App\Models\Asset::query();
        if ($activeId > 0) {
            $query->where('asset_configuration_id', $activeId);
        }

        return $query;
    }

    protected function getDefaultTableSortColumn(): ?string
    {
        return 'id';
    }

    protected function getDefaultTableSortDirection(): ?string
    {
        return 'asc';
    }

    protected function getTableRecordsPerPageSelectOptions(): array
    {
        return [50, 100, 150];
    }

    protected function getDefaultTableRecordsPerPage(): int
    {
        return 50;
    }

    public function mount(): void
    {
        parent::mount();

        // Do not redirect; show the page as-is. Ensure no stale table state (search/filters) affects results
        $this->tableSearch = null;
        $this->tableColumnSearches = [];
        $this->tableFilters = [];
        if (property_exists($this, 'tableSortColumn')) {
            $this->tableSortColumn = null;
        }
        if (property_exists($this, 'tableSortDirection')) {
            $this->tableSortDirection = null;
        }
    }

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
            Action::make('upload_asset_configuration')
                ->label('upload asset configuration')
                ->icon('heroicon-m-arrow-up-tray')
                ->color('primary')
                ->action(fn () => redirect(\App\Filament\Pages\AssetConfigurationUpload::getUrl())),
        ];
    }
}
