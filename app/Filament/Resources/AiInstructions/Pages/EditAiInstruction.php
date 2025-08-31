<?php

namespace App\Filament\Resources\AiInstructions\Pages;

use App\Filament\Resources\AiInstructions\AiInstructionResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\Auth;

class EditAiInstruction extends EditRecord
{
    protected static string $resource = AiInstructionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $data['updated_by'] = Auth::id();
        $data['updated_checksum'] = hash('sha256', $data['name'].'_updated_'.now()->timestamp);

        return $data;
    }
}
