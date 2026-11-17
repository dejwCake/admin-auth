@extends('brackets/admin-auth::admin.auth.layout.auth')

@section('title', trans('brackets/admin-auth::admin.two_factor.challenge.title'))

@section('auth-content')
	<two-factor-challenge-form
		:action="'{{ $action }}'"
		:redirect-url="'{{ $redirectUrl }}'"
		:has-totp="{{ json_encode($hasTotp ?? true) }}"
		:has-passkeys="{{ json_encode($hasPasskeys ?? false) }}"
		:passkey-options-url="'{{ $passkeyOptionsUrl ?? '' }}'"
		:passkey-url="'{{ $passkeyUrl ?? '' }}'"
		:translations="{{ json_encode([
			'title' => trans('brackets/admin-auth::admin.two_factor.challenge.title'),
			'codeText' => trans('brackets/admin-auth::admin.two_factor.challenge.code_text'),
			'recoveryCodeText' => trans('brackets/admin-auth::admin.two_factor.challenge.recovery_code_text'),
			'code' => trans('brackets/admin-auth::admin.two_factor.challenge.code'),
			'recoveryCode' => trans('brackets/admin-auth::admin.two_factor.challenge.recovery_code'),
			'button' => trans('brackets/admin-auth::admin.two_factor.challenge.button'),
			'useRecoveryCode' => trans('brackets/admin-auth::admin.two_factor.challenge.use_recovery_code'),
			'useCode' => trans('brackets/admin-auth::admin.two_factor.challenge.use_code'),
			'passkeyText' => trans('brackets/admin-auth::admin.two_factor.challenge.passkey_text'),
			'usePasskey' => trans('brackets/admin-auth::admin.two_factor.challenge.use_passkey'),
			'passkeyUnsupported' => trans('brackets/admin-auth::admin.passkeys.unsupported'),
			'or' => trans('brackets/admin-auth::admin.two_factor.challenge.or'),
		]) }}"
	></two-factor-challenge-form>
	<div class="text-center">
		<a href="{{ url('admin/logout') }}" class="auth-ghost-link">{{ trans('brackets/admin-auth::admin.profile_dropdown.logout') }}</a>
	</div>
@endsection
