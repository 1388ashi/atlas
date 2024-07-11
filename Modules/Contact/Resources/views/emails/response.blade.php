@component('mail::message')
# Your Contact
## Subject
{{ $subject }}
## Body
{{ $body }}

## Answer
{{ $answer }}

@component('mail::button', ['url' => config('app.url')])
Go To Site
@endcomponent

Thanks,<br>
{{ config('app.name') }}
@endcomponent
