<?php

use App\Filament\Resources\TeamInvitations\Pages\CreateTeamInvitation;
use App\Filament\Resources\TeamInvitations\Pages\ListTeamInvitations;
use App\Mail\TeamInvitationMail;
use App\Models\Team;
use App\Models\TeamInvitation;
use App\Models\User;
use Illuminate\Support\Facades\Mail;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\assertDatabaseHas;
use function Pest\Livewire\livewire;

function makeOwnerWithTeam(): array
{
    $owner = User::factory()->create();
    $team = Team::factory()->create(['owner_id' => $owner->id]);
    $team->addUser($owner, 'owner');
    $owner->current_team_id = $team->id;
    $owner->save();

    return [$owner, $team];
}

it('creates an invitation via the Filament resource and emails the invitee', function () {
    Mail::fake();
    [$owner, $team] = makeOwnerWithTeam();
    actingAs($owner);

    livewire(CreateTeamInvitation::class)
        ->fillForm([
            'email' => 'invitee@example.com',
            'role' => TeamInvitation::ROLE_MEMBER,
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    assertDatabaseHas('team_invitations', [
        'team_id' => $team->id,
        'email' => 'invitee@example.com',
        'role' => TeamInvitation::ROLE_MEMBER,
        'invited_by' => $owner->id,
    ]);

    Mail::assertQueued(TeamInvitationMail::class, fn (TeamInvitationMail $mail): bool => $mail->invitation->email === 'invitee@example.com');
});

it('scopes invitations listing to the current team', function () {
    [$ownerA, $teamA] = makeOwnerWithTeam();
    [$ownerB, $teamB] = makeOwnerWithTeam();

    $mine = TeamInvitation::factory()->create(['team_id' => $teamA->id, 'invited_by' => $ownerA->id]);
    $theirs = TeamInvitation::factory()->create(['team_id' => $teamB->id, 'invited_by' => $ownerB->id]);

    actingAs($ownerA);

    livewire(ListTeamInvitations::class)
        ->assertCanSeeTableRecords([$mine])
        ->assertCanNotSeeTableRecords([$theirs]);
});

it('accepts an invitation for an authenticated user whose email matches', function () {
    [$owner, $team] = makeOwnerWithTeam();
    $invitee = User::factory()->create(['email' => 'match@example.com']);
    $invitation = TeamInvitation::factory()->create([
        'team_id' => $team->id,
        'invited_by' => $owner->id,
        'email' => 'match@example.com',
    ]);

    actingAs($invitee)
        ->get(route('invitations.accept', ['token' => $invitation->token]))
        ->assertRedirect('/admin');

    expect($team->fresh()->hasUser($invitee))->toBeTrue();
    expect($invitation->fresh()->accepted_at)->not->toBeNull();
    expect($invitation->fresh()->user_id)->toBe($invitee->id);
});

it('rejects an invitation when the authenticated email does not match', function () {
    [$owner, $team] = makeOwnerWithTeam();
    $other = User::factory()->create(['email' => 'other@example.com']);
    $invitation = TeamInvitation::factory()->create([
        'team_id' => $team->id,
        'invited_by' => $owner->id,
        'email' => 'invited@example.com',
    ]);

    actingAs($other)
        ->get(route('invitations.accept', ['token' => $invitation->token]))
        ->assertForbidden();

    expect($team->fresh()->hasUser($other))->toBeFalse();
    expect($invitation->fresh()->accepted_at)->toBeNull();
});

it('rejects an expired invitation', function () {
    [$owner, $team] = makeOwnerWithTeam();
    $invitation = TeamInvitation::factory()->expired()->create([
        'team_id' => $team->id,
        'invited_by' => $owner->id,
    ]);

    $this->get(route('invitations.accept', ['token' => $invitation->token]))
        ->assertForbidden();
});

it('rejects a cancelled invitation', function () {
    [$owner, $team] = makeOwnerWithTeam();
    $invitation = TeamInvitation::factory()->cancelled()->create([
        'team_id' => $team->id,
        'invited_by' => $owner->id,
    ]);

    $this->get(route('invitations.accept', ['token' => $invitation->token]))
        ->assertForbidden();
});

it('rejects an already-accepted invitation', function () {
    [$owner, $team] = makeOwnerWithTeam();
    $invitation = TeamInvitation::factory()->accepted()->create([
        'team_id' => $team->id,
        'invited_by' => $owner->id,
    ]);

    $this->get(route('invitations.accept', ['token' => $invitation->token]))
        ->assertForbidden();
});

it('returns 404 for an unknown token', function () {
    $this->get(route('invitations.accept', ['token' => 'does-not-exist']))
        ->assertNotFound();
});
