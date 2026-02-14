@extends('brackets/admin-auth::admin.auth.layout.auth')

@section('title', trans('brackets/admin-auth::admin.login.title'))

@section('auth-content')
	<login-form
		:action="'{{ url('/admin/login') }}'"
		:redirect-url="'{{ url('/admin') }}'"
		:password-reset-url="'{{ url('/admin/password-reset') }}'"
		:translations="{{ json_encode([
			'title' => trans('brackets/admin-auth::admin.login.title'),
			'signInText' => trans('brackets/admin-auth::admin.login.sign_in_text'),
			'email' => trans('brackets/admin-auth::admin.auth_global.email'),
			'password' => trans('brackets/admin-auth::admin.auth_global.password'),
			'button' => trans('brackets/admin-auth::admin.login.button'),
			'forgotPassword' => trans('brackets/admin-auth::admin.login.forgot_password'),
		]) }}"
		:status-message="'{{ session('status', '') }}'"
		:server-errors="{{ json_encode($errors->all()) }}"
	></login-form>
@endsection
