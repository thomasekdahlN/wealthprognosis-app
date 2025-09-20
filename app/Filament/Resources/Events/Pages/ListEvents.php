<?php

namespace App\Filament\Resources\Events\Pages;

use App\Filament\Resources\Events\EventResource;
use App\Services\CurrentAssetConfiguration;
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
        $assetConfiguration = app(CurrentAssetConfiguration::class)->get();
        if ($assetConfiguration) {
            return 'Future Asset Events - ' . $assetConfiguration->name;
        }

        return 'Future Asset Events';
    }

    public function getSubheading(): ?string
    {
        return 'Manage anticipated financial events that impact your prognosis (e.g. kid(s) moving out, pension start, inheritance, debt free milestone, career change, sabbatical, major purchase, sale of property, or other life changes).';
    }
}
