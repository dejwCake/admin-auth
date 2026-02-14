@extends('brackets/admin-auth::admin.auth.layout.auth')

@section('title', trans('brackets/admin-auth::admin.password_reset.title'))

@section('auth-content')
	<reset-password-form
		:action="'{{ url('/admin/password-reset/reset') }}'"
		:token="'{{ $token }}'"
		:email="'{{ old('email', $email ?? '') }}'"
		:redirect-url="'{{ url('/admin') }}'"
		:translations="{{ json_encode([
			'title' => trans('brackets/admin-auth::admin.password_reset.title'),
			'note' => trans('brackets/admin-auth::admin.password_reset.note'),
			'email' => trans('brackets/admin-auth::admin.auth_global.email'),
			'password' => trans('brackets/admin-auth::admin.auth_global.password'),
			'passwordConfirm' => trans('brackets/admin-auth::admin.auth_global.password_confirm'),
			'button' => trans('brackets/admin-auth::admin.password_reset.button'),
		]) }}"
		:status-message="'{{ session('status', '') }}'"
		:server-errors="{{ json_encode($errors->all()) }}"
	></reset-password-form>
@endsection
