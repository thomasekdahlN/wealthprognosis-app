<?php

namespace App\Filament\Resources\TeamInvitations\Schemas;

use App\Models\TeamInvitation;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class TeamInvitationForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('email')
                    ->label('Email address')
                    ->email()
                    ->required()
                    ->maxLength(255)
                    ->disabled(fn (?TeamInvitation $record): bool => $record !== null)
                    ->helperText('The person will receive an invitation email at this address.'),
                Select::make('role')
                    ->label('Role')
                    ->options([
                        TeamInvitation::ROLE_ADMIN => 'Admin',
                        TeamInvitation::ROLE_MEMBER => 'Member',
                    ])
                    ->default(TeamInvitation::ROLE_MEMBER)
                    ->required()
                    ->native(false),
            ]);
    }
}
