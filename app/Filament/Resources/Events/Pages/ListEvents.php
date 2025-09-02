<?php

namespace App\Filament\Resources\Events\Pages;

use App\Filament\Resources\Events\EventResource;
use App\Services\AssetConfigurationSessionService;
use Filament\Resources\Pages\ListRecords;

class ListEvents extends ListRecords
{
    protected static string $resource = EventResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }

    public function getTitle(): string
    {
        return 'Events';
    }

    public function getHeading(): string
    {
        $assetConfiguration = AssetConfigurationSessionService::getActiveAssetConfiguration();
        if ($assetConfiguration) {
            return 'Future Asset Events - ' . $assetConfiguration->name;
        }

        return 'Future Asset Events';
    }

    public function getSubheading(): ?string
    {
        return 'Manage anticipated financial events that will impact your wealth projection. These events represent significant changes in income, expenses, or asset values that are expected to occur in future years and will be incorporated into your financial prognosis calculations.';
    }
}
