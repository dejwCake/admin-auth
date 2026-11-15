@extends('brackets/admin-ui::admin.layout.default')

@section('title', trans('brackets/admin-auth::admin.two_factor.title'))

@section('body')

    <div class="container-xl">

        @if (!$available)
            <div class="card">
                <div class="card-header"><i class="fa fa-shield-halved"></i> {{ trans('brackets/admin-auth::admin.two_factor.title') }}</div>
                <div class="card-body">
                    <div class="alert alert-warning mb-0 two-factor-migration-missing">
                        {{ trans('brackets/admin-auth::admin.two_factor.migration_missing') }}
                    </div>
                </div>
            </div>
        @else
            @if ($required && !$enabled)
                <div class="alert alert-info two-factor-enrolment-required">
                    {{ trans('brackets/admin-auth::admin.two_factor.enrolment_required') }}
                </div>
            @endif

            <two-factor-settings
                :enabled="{{ json_encode($enabled) }}"
                :required="{{ json_encode($required) }}"
                :enable-url="'{{ $enableUrl }}'"
                :confirm-url="'{{ $confirmUrl }}'"
                :recovery-codes-url="'{{ $recoveryCodesUrl }}'"
                :disable-url="'{{ $disableUrl }}'"
                :translations="{{ json_encode([
                    'title' => trans('brackets/admin-auth::admin.two_factor.title'),
                    'status_on' => trans('brackets/admin-auth::admin.two_factor.status_on'),
                    'status_off' => trans('brackets/admin-auth::admin.two_factor.status_off'),
                    'enable' => trans('brackets/admin-auth::admin.two_factor.enable'),
                    'enable_description' => trans('brackets/admin-auth::admin.two_factor.enable_description'),
                    'scan_qr' => trans('brackets/admin-auth::admin.two_factor.scan_qr'),
                    'secret_label' => trans('brackets/admin-auth::admin.two_factor.secret_label'),
                    'code' => trans('brackets/admin-auth::admin.two_factor.code'),
                    'confirm' => trans('brackets/admin-auth::admin.two_factor.confirm'),
                    'cancel' => trans('brackets/admin-ui::admin.btn.cancel'),
                    'recovery_codes_title' => trans('brackets/admin-auth::admin.two_factor.recovery_codes_title'),
                    'recovery_codes_description' => trans('brackets/admin-auth::admin.two_factor.recovery_codes_description'),
                    'copy' => trans('brackets/admin-auth::admin.two_factor.copy'),
                    'copied' => trans('brackets/admin-auth::admin.two_factor.copied'),
                    'download' => trans('brackets/admin-auth::admin.two_factor.download'),
                    'done' => trans('brackets/admin-auth::admin.two_factor.done'),
                    'current_password' => trans('brackets/admin-auth::admin.two_factor.current_password'),
                    'regenerate' => trans('brackets/admin-auth::admin.two_factor.regenerate'),
                    'disable' => trans('brackets/admin-auth::admin.two_factor.disable'),
                    'required_notice' => trans('brackets/admin-auth::admin.two_factor.required_notice'),
                ]) }}"
            ></two-factor-settings>
        @endif

    </div>

@endsection
