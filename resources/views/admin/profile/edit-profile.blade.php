@extends('brackets/admin-ui::admin.layout.default')

@section('title', trans('brackets/admin-auth::admin.profile.title'))

@section('body')

    <div class="container-xl">

        <profile-form
            :action="'{{ $action }}'"
            :data="{{ $adminUser->toJson() }}"
            :language-options="{{ $locales->values()->toJson() }}"
            :translations="{{ json_encode([
                'form_title' => trans('brackets/admin-auth::admin.profile.title'),
                'columns' => [
                    'first_name' => trans('brackets/admin-auth::admin.profile.columns.first_name'),
                    'last_name' => trans('brackets/admin-auth::admin.profile.columns.last_name'),
                    'email' => trans('brackets/admin-auth::admin.profile.columns.email'),
                    'language' => trans('brackets/admin-auth::admin.profile.columns.language'),
                    'current_password' => trans('brackets/admin-auth::admin.profile.columns.current_password'),
                ],
                'select_an_option' => trans('brackets/admin-auth::admin.profile.select_an_option'),
                'save' => trans('brackets/admin-ui::admin.btn.save'),
            ]) }}"
            @if($avatarCollection !== null)
            :media="{{ json_encode([
                'url' => $mediaUploadUrl ?? '',
                'collection' => $avatarCollection->getName(),
                'maxNumberOfFiles' => $avatarCollection->getMaxNumberOfFiles() ?: 1,
                'maxFileSizeInMb' => $avatarCollection->getMaxFileSize() ? round($avatarCollection->getMaxFileSize() / 1024 / 1024, 2) : 2,
                'acceptedFileTypes' => $avatarCollection->getAcceptedFileTypes() ? implode(',', $avatarCollection->getAcceptedFileTypes()) : null,
                'uploadedMedia' => $avatarMedia->values()->toArray(),
            ]) }}"
            @endif
        ></profile-form>

    </div>

@endsection
