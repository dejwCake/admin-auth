@extends('brackets/admin-auth::admin.auth.layout.auth')

@section('title', trans('brackets/admin-auth::admin.activation_form.title'))

@section('auth-content')
	<activation-form
		:action="'{{ url('/admin/activation/send') }}'"
		:translations="{{ json_encode([
			'title' => trans('brackets/admin-auth::admin.activation_form.title'),
			'note' => trans('brackets/admin-auth::admin.activation_form.note'),
			'email' => trans('brackets/admin-auth::admin.auth_global.email'),
			'button' => trans('brackets/admin-auth::admin.activation_form.button'),
		]) }}"
		:status-message="'{{ session('status', '') }}'"
		:server-errors="{{ json_encode($errors->all()) }}"
	></activation-form>
@endsection
