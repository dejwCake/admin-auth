@extends('brackets/admin-ui::admin.layout.master')

@section('content')
	<div class="container" id="app">
		<div class="row align-items-center justify-content-center auth">
			<div class="col-md-5 col-lg-4">
				@yield('auth-content')
			</div>
		</div>
	</div>
@endsection
