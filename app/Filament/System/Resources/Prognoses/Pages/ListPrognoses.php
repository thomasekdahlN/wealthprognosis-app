<?php

namespace App\Filament\System\Resources\Prognoses\Pages;

use App\Filament\System\Resources\Prognoses\PrognosisResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListPrognoses extends ListRecords
{
    protected static string $resource = PrognosisResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
