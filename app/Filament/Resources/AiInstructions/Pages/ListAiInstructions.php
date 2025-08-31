<?php

namespace App\Filament\Resources\AiInstructions\Pages;

use App\Filament\Resources\AiInstructions\AiInstructionResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListAiInstructions extends ListRecords
{
    protected static string $resource = AiInstructionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
