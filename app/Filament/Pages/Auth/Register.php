<?php

namespace App\Filament\Pages\Auth;

use App\Models\Team;
use App\Models\TeamInvitation;
use App\Models\User;
use Filament\Auth\Pages\Register as BaseRegister;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Component;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class Register extends BaseRegister
{
    public ?string $invitationToken = null;

    protected ?TeamInvitation $resolvedInvitation = null;

    public function mount(): void
    {
        $this->invitationToken = request()->query('invitation');
        $this->resolvedInvitation = $this->resolveInvitation();

        parent::mount();

        if ($this->resolvedInvitation !== null) {
            $this->data['email'] = $this->resolvedInvitation->email;
        }
    }

    protected function resolveInvitation(): ?TeamInvitation
    {
        if (empty($this->invitationToken)) {
            return null;
        }

        $invitation = TeamInvitation::query()
            ->where('token', $this->invitationToken)
            ->first();

        if ($invitation === null || ! $invitation->isPending()) {
            return null;
        }

        return $invitation;
    }

    protected function getEmailFormComponent(): Component
    {
        $field = TextInput::make('email')
            ->label(__('filament-panels::auth/pages/register.form.email.label'))
            ->email()
            ->required()
            ->maxLength(255)
            ->unique($this->getUserModel());

        if ($this->resolvedInvitation !== null) {
            $field->disabled()->dehydrated();
        }

        return $field;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    protected function handleRegistration(array $data): Model
    {
        $invitation = $this->resolveInvitation();

        if ($invitation !== null) {
            $data['email'] = $invitation->email;
        }

        return DB::transaction(function () use ($data, $invitation): User {
            /** @var User $user */
            $user = $this->getUserModel()::create($data);

            if ($invitation !== null) {
                $this->attachToInvitedTeam($user, $invitation);
            } else {
                $this->createPersonalTeam($user);
            }

            return $user;
        });
    }

    protected function attachToInvitedTeam(User $user, TeamInvitation $invitation): void
    {
        $team = $invitation->team;
        if ($team !== null) {
            $team->addUser($user, $invitation->role);
            $user->current_team_id = $team->id;
            $user->save();
        }

        $invitation->accepted_at = now();
        $invitation->user_id = $user->id;
        $invitation->save();
    }

    protected function createPersonalTeam(User $user): void
    {
        $team = Team::create([
            'name' => trim($user->name).' personal',
            'owner_id' => $user->id,
            'is_active' => true,
        ]);

        $team->addUser($user, 'owner');

        $user->current_team_id = $team->id;
        $user->save();
    }
}
