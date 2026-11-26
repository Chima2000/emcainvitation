<x-mail::message>
# {{ __('Hello :name', ['name' => $recipientName]) }}

{{ __('Your :days-day trial is ready and runs until :date. Open the link to start. It works once for a new password, then you sign in with your phone.', ['days' => $days, 'date' => $endsOn]) }}

<x-mail::button :url="$url">
{{ __('Start your trial') }}
</x-mail::button>

{{ __('If the button does not open, copy this address into your browser:') }}

{{ $url }}

{{ __('Thanks') }},<br>
{{ config('app.name') }}
</x-mail::message>
