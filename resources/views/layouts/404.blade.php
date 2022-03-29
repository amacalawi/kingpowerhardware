<!DOCTYPE html>
<html lang="en" >
    <head>
        <meta charset="utf-8" />
        <title>
            PIMS App | Login
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
        </script>
    </head>
    <body id="login" class="bg-white">
    <!-- <body id="{{ Request::segment(3) }}-{{ Request::segment(4) }}" class="m-page--fluid m--skin- m-content--skin-light2 m-header--fixed m-header--fixed-mobile m-aside-left--enabled m-aside-left--skin-light m-aside-left--fixed m-aside-left--offcanvas m-aside-left--minimize m-brand--minimize m-footer--push m-aside--offcanvas-default"> -->

        <!-- begin:: Page -->
		<main>
			@yield('content')
		</main>
		<!-- end:: Page -->
                   
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
