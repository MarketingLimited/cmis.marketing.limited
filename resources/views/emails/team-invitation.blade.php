<x-mail::message>
# You've Been Invited!

You have been invited by **{{ $inviterName }}** to join **{{ $organizationName }}** on CMIS.

**Your Role:** {{ $roleName }}

@if($isNewUser)
Click the button below to create your account and join the team:
@else
Click the button below to accept this invitation:
@endif

<x-mail::button :url="$acceptUrl" color="primary">
@if($isNewUser)
Create Account & Join
@else
Accept Invitation
@endif
</x-mail::button>

This invitation will expire on **{{ $expiresAt }}**.

If you did not expect this invitation, you can safely ignore this email.

Thanks,<br>
{{ config('app.name') }}

<x-mail::subcopy>
If you're having trouble clicking the button, copy and paste the URL below into your web browser: {{ $acceptUrl }}
</x-mail::subcopy>
</x-mail::message>
