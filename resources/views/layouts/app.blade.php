<!DOCTYPE html>
<html lang="en" >
    <head>
        <meta charset="utf-8" />
        <title>
            PIMS App | {{ (strlen(Request::segment(3)) > 0)  ? ucfirst(Request::segment(2)) .' - '.ucfirst(Request::segment(3)) : ucfirst(Request::segment(2)) }}
        </title>
        <meta name="description" content="Latest updates and statistic charts">
        <meta http-equiv="X-UA-Compatible" content="IE=edge">
        <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <meta name="_token" content="{{ csrf_token() }}">
        <meta name="base-url" content="{{ url('/') }}">
        <link href="{{ asset('assets/plugins/global/plugins.bundle.css') }}" rel="stylesheet" type="text/css" />
		<link href="{{ asset('assets/css/style.bundle.css') }}" rel="stylesheet" type="text/css" />
        <link href="{{ asset('assets/css/style.css') }}" rel="stylesheet" type="text/css" />
        <!-- <link href="{{ asset('assets/css/sass.css') }}" rel="stylesheet" type="text/css" /> -->
        <link href="{{ asset('assets/css/responsive.css') }}" rel="stylesheet" type="text/css" />
        <!-- <link rel="shortcut icon" href="{{ asset('assets/demo/demo7/media/img/logo/favicon.ico') }}" /> -->
        @stack('styles')
        <script>
            var base_url = "{{ url('/') }}/";
            var _token = "{{ csrf_token() }}";
            var segment = "{{ request()->segment(count(request()->segments())) }}";
        </script>
    </head>
    @if (strlen(Request::segment(3)) > 0)
        <body id="{{ Request::segment(2).'-'.Request::segment(3)}}" class="header-fixed header-tablet-and-mobile-fixed toolbar-enabled toolbar-fixed toolbar-tablet-and-mobile-fixed" style="--kt-toolbar-height:55px;--kt-toolbar-height-tablet-and-mobile:55px">
    @else
        <body id="{{ Request::segment(2) }}" class="header-fixed header-tablet-and-mobile-fixed toolbar-enabled toolbar-fixed toolbar-tablet-and-mobile-fixed" style="--kt-toolbar-height:55px;--kt-toolbar-height-tablet-and-mobile:55px">
    @endif
    <!-- <body id="{{ Request::segment(3) }}-{{ Request::segment(4) }}" class="m-page--fluid m--skin- m-content--skin-light2 m-header--fixed m-header--fixed-mobile m-aside-left--enabled m-aside-left--skin-light m-aside-left--fixed m-aside-left--offcanvas m-aside-left--minimize m-brand--minimize m-footer--push m-aside--offcanvas-default"> -->

        <div class="d-flex flex-column flex-root">
            <div class="page d-flex flex-row flex-column-fluid">
                <div class="wrapper d-flex flex-column flex-row-fluid" id="kt_wrapper">
                    <!-- BEGIN: Header -->
                    @include('templates.header')
                    <!-- END: Header -->  

                    <div class="content d-flex flex-column flex-column-fluid" id="kt_content">
                        <!-- BEGIN: Header -->
                        @include('templates.toolbar')
                        <!-- END: Header -->  
                        <div class="post d-flex flex-column-fluid" id="kt_post">
                            <div id="kt_content_container" class="container">
                                @yield('content')   
                            </div>
                        </div>
                    </div>

                    <!-- BEGIN: Footer -->
                    @include('templates.footer')
                    <!-- END: Footer -->  
                </div>
            </div>
        </div>

        <!--begin::Scrolltop-->
        @include('templates.scroll-to-top')
		<!--end::Scrolltop-->
                   
        <!--begin::Base Scripts -->
        <script src="{{ asset('assets/plugins/global/plugins.bundle.js') }}"></script>
		<script src="{{ asset('assets/js/scripts.bundle.js') }}"></script>
        <script type="text/javascript">
            $.ajaxSetup({
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                }
            });
        </script>
        <!--end::Base Scripts -->   
        
        <!--begin::append script-->
        @stack('scripts')
        <!--end::append script-->
    </body>
</html>
