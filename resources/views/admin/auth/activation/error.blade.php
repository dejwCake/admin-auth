@extends('brackets/admin-auth::admin.auth.layout.auth')

@section('title', trans('brackets/admin-auth::admin.activation_form.title'))

@section('auth-content')
	<activation-error
		:translations="{{ json_encode([
			'title' => trans('brackets/admin-auth::admin.activation_form.title'),
		]) }}"
		:server-errors="{{ json_encode($errors->all()) }}"
	></activation-error>
@endsection
