<?php

namespace App\Filament\Resources\TeamInvitations\Pages;

use App\Filament\Resources\TeamInvitations\TeamInvitationResource;
use App\Mail\TeamInvitationMail;
use App\Models\TeamInvitation;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Mail;

class CreateTeamInvitation extends CreateRecord
{
    protected static string $resource = TeamInvitationResource::class;

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $user = auth()->user();
        $data['team_id'] = $user?->current_team_id;
        $data['invited_by'] = $user?->id;
        $data['email'] = strtolower(trim((string) ($data['email'] ?? '')));

        return $data;
    }

    protected function afterCreate(): void
    {
        /** @var TeamInvitation $record */
        $record = $this->record;
        Mail::to($record->email)->send(new TeamInvitationMail($record));
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
