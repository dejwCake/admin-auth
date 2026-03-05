@extends('brackets/admin-auth::admin.auth.layout.auth')

@section('title', trans('brackets/admin-auth::admin.password_reset.title'))

@section('auth-content')
	<reset-password-form
		:action="'{{ $action }}'"
		:token="'{{ $token }}'"
		:email="'{{ old('email', $email ?? '') }}'"
		:redirect-url="'{{ $redirectUrl }}'"
		:login-url="'{{ $loginUrl }}'"
		:translations="{{ json_encode([
			'title' => trans('brackets/admin-auth::admin.password_reset.title'),
			'note' => trans('brackets/admin-auth::admin.password_reset.note'),
			'email' => trans('brackets/admin-auth::admin.auth_global.email'),
			'password' => trans('brackets/admin-auth::admin.auth_global.password'),
			'passwordConfirm' => trans('brackets/admin-auth::admin.auth_global.password_confirm'),
			'button' => trans('brackets/admin-auth::admin.password_reset.button'),
			'backToLogin' => trans('brackets/admin-auth::admin.password_reset.back_to_login'),
		]) }}"
        :status-message="'{{ $statusMessage }}'"
		:server-errors="{{ json_encode($errors->all()) }}"
	></reset-password-form>
@endsection
