@extends('brackets/admin-ui::admin.layout.default')

@section('title', trans('brackets/admin-auth::admin.password_change.title'))

@section('body')

    <div class="container-xl">

        <password-change-form
            :action="'{{ $action }}'"
            :translations="{{ json_encode([
                'form_title' => trans('brackets/admin-auth::admin.password_change.title'),
                'columns' => [
                    'current_password' => trans('brackets/admin-auth::admin.password_change.columns.current_password'),
                    'password' => trans('brackets/admin-auth::admin.password_change.columns.password'),
                    'password_repeat' => trans('brackets/admin-auth::admin.password_change.columns.password_repeat'),
                ],
                'save' => trans('brackets/admin-ui::admin.btn.save'),
            ]) }}"
        ></password-change-form>

    </div>

@endsection
