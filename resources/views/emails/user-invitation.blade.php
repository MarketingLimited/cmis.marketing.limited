@component('mail::message')
# Welcome to {{ $organizationName }}!

You've been invited by **{{ $inviterName }}** ({{ $inviterEmail }}) to join **{{ $organizationName }}** on the CMIS Marketing Platform.

## Your Role
You'll be joining as a **{{ $roleName }}**, which will give you access to collaborate on campaigns, content, and analytics with your team.

@component('mail::panel')
### What you can do next:
- Create and manage marketing campaigns
- Collaborate with your team on content creation
- Track performance metrics and analytics
- Manage social media publishing schedules
@endcomponent

@component('mail::button', ['url' => $acceptUrl, 'color' => 'success'])
Accept Invitation
@endcomponent

## Important Information
- This invitation will expire on **{{ $expiresAt }}**
- If you don't have a CMIS account yet, you'll be able to create one when you accept this invitation
- If you already have an account, this invitation will add you to the {{ $organizationName }} organization

## Need Help?
If you have any questions about this invitation or the CMIS platform, please contact your team administrator or reply to this email.

---

**Security Note:** This invitation is unique to you and should not be shared. If you didn't expect this invitation or believe it was sent in error, you can safely ignore this email.

Thanks,<br>
{{ config('app.name') }} Team

@component('mail::subcopy')
If you're having trouble clicking the "Accept Invitation" button, copy and paste the URL below into your web browser:

[{{ $acceptUrl }}]({{ $acceptUrl }})

---

This invitation was sent to {{ $invitedUser->email }} by {{ $inviterName }} from {{ $organizationName }}.
@endcomponent
@endcomponent
