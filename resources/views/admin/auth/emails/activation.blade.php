<x-mail::message>
# {{ trans('brackets/admin-auth::activations.email.greeting') }}

{{ trans('brackets/admin-auth::activations.email.line') }}

<x-mail::button :url="$actionUrl">
{{ trans('brackets/admin-auth::activations.email.action') }}
</x-mail::button>

{{ trans('brackets/admin-auth::activations.email.notRequested') }}

{{ trans('brackets/admin-auth::activations.email.salutation') }}

{{ config('app.name') }}

<x-slot:subcopy>
{{ trans('brackets/admin-auth::activations.email.subcopy', [
    'actionText' => trans('brackets/admin-auth::activations.email.action'),
    'actionUrl' => $actionUrl,
    ]) }}
</x-slot:subcopy>
</x-mail::message>
