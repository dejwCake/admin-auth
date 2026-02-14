@extends('brackets/admin-auth::admin.auth.layout.auth')

@section('title', trans('brackets/admin-auth::admin.forgot_password.title'))

@section('auth-content')
	<forgot-password-form
		:action="'{{ url('/admin/password-reset/send') }}'"
		:translations="{{ json_encode([
			'title' => trans('brackets/admin-auth::admin.forgot_password.title'),
			'note' => trans('brackets/admin-auth::admin.forgot_password.note'),
			'email' => trans('brackets/admin-auth::admin.auth_global.email'),
			'button' => trans('brackets/admin-auth::admin.forgot_password.button'),
		]) }}"
		:status-message="'{{ session('status', '') }}'"
		:server-errors="{{ json_encode($errors->all()) }}"
	></forgot-password-form>
@endsection
