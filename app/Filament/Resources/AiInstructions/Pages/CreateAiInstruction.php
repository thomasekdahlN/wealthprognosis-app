<?php

namespace App\Filament\Resources\AiInstructions\Pages;

use App\Filament\Resources\AiInstructions\AiInstructionResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Auth;

class CreateAiInstruction extends CreateRecord
{
    protected static string $resource = AiInstructionResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $user = Auth::user();

        $data['user_id'] = $user->id;
        $data['team_id'] = $user->current_team_id;
        $data['created_by'] = $user->id;
        $data['updated_by'] = $user->id;
        $data['created_checksum'] = hash('sha256', $data['name'].'_created');
        $data['updated_checksum'] = hash('sha256', $data['name'].'_updated');

        return $data;
    }
}
