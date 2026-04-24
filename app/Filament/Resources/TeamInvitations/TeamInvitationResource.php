<?php

namespace App\Filament\Resources\TeamInvitations;

use App\Filament\Resources\TeamInvitations\Pages\CreateTeamInvitation;
use App\Filament\Resources\TeamInvitations\Pages\EditTeamInvitation;
use App\Filament\Resources\TeamInvitations\Pages\ListTeamInvitations;
use App\Filament\Resources\TeamInvitations\Schemas\TeamInvitationForm;
use App\Filament\Resources\TeamInvitations\Tables\TeamInvitationsTable;
use App\Models\TeamInvitation;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class TeamInvitationResource extends Resource
{
    protected static ?string $model = TeamInvitation::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUserPlus;

    protected static ?string $navigationLabel = 'Team invitations';

    protected static ?string $recordTitleAttribute = 'email';

    protected static ?string $maxContentWidth = 'full';

    public static function getEloquentQuery(): Builder
    {
        $currentTeamId = auth()->user()?->current_team_id;

        return parent::getEloquentQuery()
            ->where('team_id', $currentTeamId ?? -1);
    }

    public static function form(Schema $schema): Schema
    {
        return TeamInvitationForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return TeamInvitationsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListTeamInvitations::route('/'),
            'create' => CreateTeamInvitation::route('/create'),
            'edit' => EditTeamInvitation::route('/{record}/edit'),
        ];
    }

    public static function canAccess(): bool
    {
        return auth()->check() && auth()->user()?->current_team_id !== null;
    }
}
