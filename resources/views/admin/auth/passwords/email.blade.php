@extends('brackets/admin-auth::admin.auth.layout.auth')

@section('title', trans('brackets/admin-auth::admin.forgot_password.title'))

@section('auth-content')
	<forgot-password-form
		:action="'{{ $action }}'"
		:login-url="'{{ $loginUrl }}'"
		:translations="{{ json_encode([
			'title' => trans('brackets/admin-auth::admin.forgot_password.title'),
			'note' => trans('brackets/admin-auth::admin.forgot_password.note'),
			'email' => trans('brackets/admin-auth::admin.auth_global.email'),
			'button' => trans('brackets/admin-auth::admin.forgot_password.button'),
			'backToLogin' => trans('brackets/admin-auth::admin.forgot_password.back_to_login'),
		]) }}"
        :status-message="'{{ $statusMessage }}'"
		:server-errors="{{ json_encode($errors->all()) }}"
	></forgot-password-form>
@endsection
