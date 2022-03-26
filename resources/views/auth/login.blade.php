@extends('layouts.login')

@section('content')
<!--begin::Main-->
<div class="d-flex flex-column flex-root">
    <!--begin::Authentication - Sign-in -->
    <div class="d-flex flex-column flex-column-fluid bgi-position-y-bottom position-x-center bgi-no-repeat bgi-size-contain bgi-attachment-fixed" style="background-image: url(assets/media/illustrations/progress-hd.png)">
        <!--begin::Content-->
        <div class="d-flex flex-center flex-column flex-column-fluid p-10 pb-lg-20">
            <!--begin::Logo-->
            <h1 class="mb-4" style="color:#009ef7; font-size: 60px;font-family: Poppins-SemiBold;text-shadow: 2px 2px #000;">P I M S</h1>
            <!--end::Logo-->
            <!--begin::Wrapper-->
            <div class="w-lg-500px bg-white rounded shadow-sm p-10 p-lg-15 mx-auto">
                <!--begin::Form-->
                <form class="form w-100" method="post" novalidate="novalidate" id="kt_sign_in_form" action="{{ route('login') }}">
                    <!--begin::Heading-->
                    @csrf
                    <div class="text-center mb-10">
                        <!--begin::Title-->
                        <h1 class="text-dark mb-3">Sign In to PIMS App</h1>
                        <!--end::Title-->
                    </div>
                    <!--begin::Heading-->
                    <!--begin::Input group-->
                    <div class="fv-row mb-10">
                        <!--begin::Label-->
                        <label class="form-label fs-6 fw-bolder text-dark">{{ __('Username') }}</label>
                        <!--end::Label-->
                        <!--begin::Input-->
                        <input class="form-control form-control-lg form-control-solid" type="text" name="username" value="{{ !(strlen($errors->first('username')) > 0) ? old('username') : '' }}" autocomplete="off" tabindex="1" />
                        <span class="invalid-feedback text-center" role="alert" style="display:block">
                            <strong>{{ $errors->first('username') }}</strong>
                        </span>
                        <!--end::Input-->
                    </div>
                    <!--end::Input group-->
                    <!--begin::Input group-->
                    <div class="fv-row mb-10">
                        <!--begin::Wrapper-->
                        <div class="d-flex flex-stack mb-2">
                            <!--begin::Label-->
                            <label class="form-label fw-bolder text-dark fs-6 mb-0">{{ __('Password?') }}</label>
                            <!--end::Label-->
                            <!--begin::Link-->
                            @if (Route::has('password.request'))
                                <a href="{{ route('password.request') }}" class="link-primary fs-6 fw-bolder">{{ __('Forgot Your Password?') }}</a>
                            @endif
                            <!--end::Link-->
                        </div>
                        <!--end::Wrapper-->
                        <!--begin::Input-->
                        <input class="form-control form-control-lg form-control-solid" type="password" name="password" autocomplete="off" tabindex="1"/>
                        <!--end::Input-->
                    </div>
                    <!--end::Input group-->
                    <!--begin::Actions-->
                    <div class="text-center">
                        <!--begin::Submit button-->
                        <button type="submit" id="kt_sign_in_submit" class="btn btn-lg btn-primary w-100 mb-5" tabindex="3">
                            <span class="indicator-label">Continue</span>
                            <span class="indicator-progress">Please wait...
                            <span class="spinner-border spinner-border-sm align-middle ms-2"></span></span>
                        </button>
                        <!--end::Submit button-->
                    </div>
                    <!--end::Actions-->
                </form>
                <!--end::Form-->
            </div>
            <!--end::Wrapper-->
        </div>
        <!--end::Content-->
        <!--begin::Footer-->
        <div class="d-flex flex-center flex-column-auto p-10">
            <!--begin::Links-->
            <div class="d-flex align-items-center fw-bold fs-6">
                <a href="https://keenthemes.com/faqs" class="text-muted text-hover-primary px-2">About</a>
                <a href="mailto:support@keenthemes.com" class="text-muted text-hover-primary px-2">Contact</a>
                <a href="https://1.envato.market/EA4JP" class="text-muted text-hover-primary px-2">Contact Us</a>
            </div>
            <!--end::Links-->
        </div>
        <!--end::Footer-->
    </div>
    <!--end::Authentication - Sign-in-->
</div>
@endsection

@push('scripts')
    <script src="{{ asset('js/forms/login.js') }}" type="text/javascript"></script>
@endpush
