@extends('brackets/admin-ui::admin.layout.default')

@section('title', trans('brackets/admin-auth::admin.passkeys.title'))

@section('body')

    <div class="container-xl">

        <passkey-settings
            :passkeys="{{ json_encode($passkeys) }}"
            :available="{{ json_encode($available) }}"
            :options-url="'{{ $optionsUrl }}'"
            :store-url="'{{ $storeUrl }}'"
            :passkey-url="'{{ $passkeyUrl }}'"
            :translations="{{ json_encode([
                'title' => trans('brackets/admin-auth::admin.passkeys.title'),
                'description' => trans('brackets/admin-auth::admin.passkeys.description'),
                'empty' => trans('brackets/admin-auth::admin.passkeys.empty'),
                'add' => trans('brackets/admin-auth::admin.passkeys.add'),
                'name' => trans('brackets/admin-auth::admin.passkeys.name'),
                'default_name' => trans('brackets/admin-auth::admin.passkeys.default_name'),
                'current_password' => trans('brackets/admin-auth::admin.passkeys.current_password'),
                'rename' => trans('brackets/admin-auth::admin.passkeys.rename'),
                'save' => trans('brackets/admin-ui::admin.btn.save'),
                'remove' => trans('brackets/admin-auth::admin.passkeys.remove'),
                'remove_confirm' => trans('brackets/admin-auth::admin.passkeys.remove_confirm'),
                'cancel' => trans('brackets/admin-ui::admin.btn.cancel'),
                'added' => trans('brackets/admin-auth::admin.passkeys.added_at'),
                'last_used' => trans('brackets/admin-auth::admin.passkeys.last_used'),
                'never_used' => trans('brackets/admin-auth::admin.passkeys.never_used'),
                'unsupported' => trans('brackets/admin-auth::admin.passkeys.unsupported'),
                'migration_missing' => trans('brackets/admin-auth::admin.passkeys.migration_missing'),
                'failed' => trans('brackets/admin-auth::admin.passkeys.invalid'),
            ]) }}"
        ></passkey-settings>

    </div>

@endsection
