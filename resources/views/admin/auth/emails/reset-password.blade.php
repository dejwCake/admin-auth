<x-mail::message>
# {{ trans('brackets/admin-auth::resets.email.greeting') }}

{{ trans('brackets/admin-auth::resets.email.line') }}

<x-mail::button :url="$actionUrl">
{{ trans('brackets/admin-auth::resets.email.action') }}
</x-mail::button>

{{ trans('brackets/admin-auth::resets.email.notRequested') }}

{{ trans('brackets/admin-auth::resets.email.salutation') }}

{{ config('app.name') }}

<x-slot:subcopy>
{{ trans('brackets/admin-auth::resets.email.subcopy', ['actionText' => trans('brackets/admin-auth::resets.email.action'), 'actionUrl' => $actionUrl]) }}
</x-slot:subcopy>
</x-mail::message>
