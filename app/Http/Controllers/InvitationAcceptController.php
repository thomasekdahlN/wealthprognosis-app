<?php

namespace App\Http\Controllers;

use App\Models\TeamInvitation;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\HttpException;

class InvitationAcceptController extends Controller
{
    public function __invoke(Request $request, string $token): RedirectResponse
    {
        $invitation = TeamInvitation::query()
            ->where('token', $token)
            ->first();

        if ($invitation === null) {
            throw new HttpException(404, 'Invitation not found.');
        }

        if ($invitation->accepted_at !== null) {
            abort(403, 'This invitation has already been accepted.');
        }

        if ($invitation->cancelled_at !== null) {
            abort(403, 'This invitation has been cancelled.');
        }

        if ($invitation->isExpired()) {
            abort(403, 'This invitation has expired.');
        }

        $invitationEmail = strtolower($invitation->email);

        if ($request->user() !== null) {
            $currentEmail = strtolower((string) $request->user()->email);

            if ($currentEmail !== $invitationEmail) {
                abort(403, 'This invitation was issued to a different email address. Please log in with the invited email address.');
            }

            $this->acceptFor($invitation, $request->user());

            return redirect('/admin')->with('status', 'Invitation accepted.');
        }

        $existingUser = User::query()->whereRaw('LOWER(email) = ?', [$invitationEmail])->first();

        if ($existingUser !== null) {
            session(['pending_invitation_token' => $invitation->token]);

            return redirect('/admin/login')->with('status', 'Log in to accept your invitation.');
        }

        return redirect('/admin/register?invitation='.$invitation->token);
    }

    private function acceptFor(TeamInvitation $invitation, User $user): void
    {
        $team = $invitation->team;
        if ($team !== null) {
            $team->addUser($user, $invitation->role);
        }

        if ($user->current_team_id === null && $team !== null) {
            $user->current_team_id = $team->id;
            $user->save();
        }

        $invitation->accepted_at = now();
        $invitation->user_id = $user->id;
        $invitation->save();
    }
}
