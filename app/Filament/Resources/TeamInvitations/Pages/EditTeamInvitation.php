<?php

namespace App\Filament\Resources\TeamInvitations\Pages;

use App\Filament\Resources\TeamInvitations\TeamInvitationResource;
use App\Mail\TeamInvitationMail;
use App\Models\TeamInvitation;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\Mail;

class EditTeamInvitation extends EditRecord
{
    protected static string $resource = TeamInvitationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('resend')
                ->label('Resend')
                ->icon('heroicon-o-envelope')
                ->color('info')
                ->visible(fn (TeamInvitation $record): bool => $record->accepted_at === null && $record->cancelled_at === null)
                ->action(function (TeamInvitation $record): void {
                    $record->expires_at = now()->addDays(7);
                    $record->save();
                    Mail::to($record->email)->send(new TeamInvitationMail($record));
                    Notification::make()->title('Invitation resent')->success()->send();
                }),
            Action::make('cancel')
                ->label('Cancel invitation')
                ->icon('heroicon-o-x-circle')
                ->color('danger')
                ->requiresConfirmation()
                ->visible(fn (TeamInvitation $record): bool => $record->accepted_at === null && $record->cancelled_at === null)
                ->action(function (TeamInvitation $record): void {
                    $record->cancelled_at = now();
                    $record->save();
                    Notification::make()->title('Invitation cancelled')->success()->send();
                }),
            DeleteAction::make(),
        ];
    }
}
