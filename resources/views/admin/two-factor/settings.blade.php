@extends('brackets/admin-ui::admin.layout.default')

@section('title', trans('brackets/admin-auth::admin.two_factor.title'))

@section('body')

    <div class="container-xl">
        <two-factor-overview
            :factors="{{ json_encode($factors) }}"
            :required="{{ json_encode($required) }}"
            :translations="{{ json_encode([
                'title' => trans('brackets/admin-auth::admin.two_factor.title'),
                'status_on' => trans('brackets/admin-auth::admin.two_factor.status_on'),
                'status_off' => trans('brackets/admin-auth::admin.two_factor.status_off'),
                'active_via' => trans('brackets/admin-auth::admin.two_factor.active_via'),
                'enrolment_required' => trans('brackets/admin-auth::admin.two_factor.enrolment_required'),
            ]) }}"
        ></two-factor-overview>
    </div>

@endsection
