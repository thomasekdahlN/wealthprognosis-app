<?php

namespace App\Filament\Resources\TeamInvitations\Pages;

use App\Filament\Resources\TeamInvitations\TeamInvitationResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListTeamInvitations extends ListRecords
{
    protected static string $resource = TeamInvitationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
