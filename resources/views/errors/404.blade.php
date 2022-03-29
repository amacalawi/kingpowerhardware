@extends('layouts.404')

@section('content') 
<div class="d-flex flex-column flex-root">
<!--begin::Authentication - Error 404 -->
<div class="d-flex flex-column flex-column-fluid bgi-position-y-bottom position-x-center bgi-no-repeat bgi-size-contain bgi-attachment-fixed" style="background-image: url({{ asset('assets/media/illustrations/progress-hd.png') }})">
    <!--begin::Content-->
    <div class="d-flex flex-column flex-column-fluid text-center p-10 py-lg-20">
        <!--begin::Logo-->
        <a href="{{ url('/') }}" class="mb-10 pt-lg-20">
            <h1 class="mb-4" style="color:#009ef7; font-size: 60px;font-family: Poppins-SemiBold;text-shadow: 2px 2px #000;">P I M S</h1>
        </a>
        <!--end::Logo-->
        <!--begin::Wrapper-->
        <div class="pt-lg-10">
            <!--begin::Logo-->
            <h1 class="fw-bolder fs-4x text-gray-700 mb-10">Page Not Found</h1>
            <!--end::Logo-->
            <!--begin::Message-->
            <div class="fw-bold fs-3 text-gray-400 mb-15">The page you looked not found!
            </div>
            <!--end::Message-->
            <!--begin::Action-->
            <div class="text-center">
                <a href="{{ url('/') }}" class="btn btn-lg btn-primary fw-bolder">Go to homepage</a>
            </div>
            <!--end::Action-->
        </div>
        <!--end::Wrapper-->
    </div>
    <!--end::Content-->
    <!--begin::Footer-->
    <div class="d-flex flex-center flex-column-auto p-10">
        <!--begin::Links-->
        <div class="d-flex align-items-center fw-bold fs-6">
            <a href="#" class="text-muted text-hover-primary px-2">About</a>
            <a href="#" class="text-muted text-hover-primary px-2">Contact</a>
            <a href="#" class="text-muted text-hover-primary px-2">Contact Us</a>
            <br /><br /><br /><br />
        </div>
        <!--end::Links-->
    </div>
    <!--end::Footer-->
</div>
<!--end::Authentication - Error 404-->
</div>
@endsection