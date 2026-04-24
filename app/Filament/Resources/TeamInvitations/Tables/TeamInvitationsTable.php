<?php

namespace App\Filament\Resources\TeamInvitations\Tables;

use App\Mail\TeamInvitationMail;
use App\Models\TeamInvitation;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Mail;

class TeamInvitationsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('email')
                    ->label('Email address')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('role')
                    ->label('Role')
                    ->badge()
                    ->color(fn (string $state): string => $state === TeamInvitation::ROLE_ADMIN ? 'warning' : 'primary')
                    ->sortable(),
                TextColumn::make('status')
                    ->label('Status')
                    ->state(fn (TeamInvitation $record): string => $record->status())
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'pending' => 'info',
                        'accepted' => 'success',
                        'expired' => 'warning',
                        'cancelled' => 'danger',
                        default => 'gray',
                    }),
                TextColumn::make('invitedBy.name')
                    ->label('Invited by')
                    ->sortable()
                    ->toggleable(),
                TextColumn::make('expires_at')
                    ->label('Expires')
                    ->dateTime('Y-m-d H:i')
                    ->sortable(),
                TextColumn::make('accepted_at')
                    ->label('Accepted')
                    ->dateTime('Y-m-d H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('cancelled_at')
                    ->label('Cancelled')
                    ->dateTime('Y-m-d H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('created_at')
                    ->label('Created')
                    ->dateTime('Y-m-d H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('role')
                    ->label('Role')
                    ->options([
                        TeamInvitation::ROLE_ADMIN => 'Admin',
                        TeamInvitation::ROLE_MEMBER => 'Member',
                    ]),
                SelectFilter::make('status')
                    ->label('Status')
                    ->options([
                        'pending' => 'Pending',
                        'accepted' => 'Accepted',
                        'expired' => 'Expired',
                        'cancelled' => 'Cancelled',
                    ])
                    ->query(function ($query, array $data) {
                        $value = $data['value'] ?? null;
                        if (! $value) {
                            return $query;
                        }

                        return match ($value) {
                            'pending' => $query->whereNull('accepted_at')->whereNull('cancelled_at')->where('expires_at', '>', now()),
                            'accepted' => $query->whereNotNull('accepted_at'),
                            'expired' => $query->whereNull('accepted_at')->whereNull('cancelled_at')->where('expires_at', '<=', now()),
                            'cancelled' => $query->whereNotNull('cancelled_at'),
                            default => $query,
                        };
                    }),
            ])
            ->filtersLayout(FiltersLayout::AboveContent)
            ->toggleColumnsTriggerAction(fn ($action) => $action->modalHeading('Choose columns'))
            ->defaultSort('created_at', 'desc')
            ->paginated([50, 100, 150])
            ->defaultPaginationPageOption(50)
            ->paginationPageOptions([50, 100, 150])
            ->striped()
            ->persistFiltersInSession()
            ->recordActions([
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
                    ->label('Cancel')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->visible(fn (TeamInvitation $record): bool => $record->accepted_at === null && $record->cancelled_at === null)
                    ->action(function (TeamInvitation $record): void {
                        $record->cancelled_at = now();
                        $record->save();
                        Notification::make()->title('Invitation cancelled')->success()->send();
                    }),
            ]);
    }
}
