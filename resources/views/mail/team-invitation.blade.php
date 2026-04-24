<x-mail::message>
    # You have been invited to join {{ $teamName }}

    {{ $inviterName }} has invited you to join **{{ $teamName }}** as a **{{ ucfirst($role) }}** on
    {{ config('app.name') }}.

    Click the button below to accept the invitation and set up your account.

    <x-mail::button :url="$acceptUrl">
        Accept invitation
    </x-mail::button>

    This invitation expires on **{{ $expiresAt->format('Y-m-d H:i') }}**. If you were not expecting this email you can
    safely ignore it.

    Thanks,<br>
    {{ config('app.name') }}
</x-mail::message>
