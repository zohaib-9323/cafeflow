<x-mail::message>
# New website enquiry

**From:** {{ $data['name'] }} ({{ $data['email'] }})
**Subject:** {{ $data['subject'] }}

<x-mail::panel>
{{ $data['message'] }}
</x-mail::panel>

Reply to this email to answer them directly.
</x-mail::message>
