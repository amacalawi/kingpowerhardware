<!--begin::Toolbar-->
<div class="toolbar" id="kt_toolbar">
    <!--begin::Container-->
    <div id="kt_toolbar_container" class="container-fluid d-flex flex-stack">
        <!--begin::Page title-->
        <div data-kt-place="true" data-kt-place-mode="prepend" data-kt-place-parent="{default: '#kt_content_container', 'lg': '#kt_toolbar_container'}" class="page-title d-flex align-items-center me-3 flex-wrap mb-5 mb-lg-0 lh-1">
            <!--begin::Title-->
            <h1 class="d-flex align-items-center text-dark fw-bolder my-1 fs-3">
                @if (strtolower(Request::segment(2)) == 'components')
                    {{ ucwords(str_replace('-',' ',Request::segment(3))) }} 
                @else
                    @if (strlen(Request::segment(3)) > 0) 
                        {{ ucwords(str_replace('-',' ',Request::segment(2))) }} - {{ ucwords(str_replace('-',' ',Request::segment(3))) }}
                    @else
                        {{ ucwords(str_replace('-',' ',Request::segment(2))) }}
                    @endif
                @endif
            </h1>
            <!--end::Title-->
        </div>
        <!--end::Page title-->
        <!--begin::Actions-->
        <div class="d-flex align-items-center py-1">
            @if (strtolower(Request::segment(2)) == 'components' || (strtolower(Request::segment(2)) == 'items' && strtolower(Request::segment(3)) == 'listing'))
                @if (!Request::segment(4))
                <a href="{{ url('/auth').'/'.Request::segment(2).'/'.Request::segment(3).'/inactive' }}" class="text-danger" data-kt-menu-trigger="click" data-kt-menu-placement="bottom-end" data-kt-menu-flip="top-end">
                    Move to Inactive Records
                    <!--begin::Svg Icon | path: assets/media/icons/duotone/Navigation/Angle-double-right.svg-->
                    <span class="svg-icon svg-icon-5 svg-icon-gray-500 me-1"><svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" width="24px" height="24px" viewBox="0 0 24 24" version="1.1">
                        <g stroke="none" stroke-width="1" fill="none" fill-rule="evenodd">
                            <polygon points="0 0 24 0 24 24 0 24"/>
                            <path d="M12.2928955,6.70710318 C11.9023712,6.31657888 11.9023712,5.68341391 12.2928955,5.29288961 C12.6834198,4.90236532 13.3165848,4.90236532 13.7071091,5.29288961 L19.7071091,11.2928896 C20.085688,11.6714686 20.0989336,12.281055 19.7371564,12.675721 L14.2371564,18.675721 C13.863964,19.08284 13.2313966,19.1103429 12.8242777,18.7371505 C12.4171587,18.3639581 12.3896557,17.7313908 12.7628481,17.3242718 L17.6158645,12.0300721 L12.2928955,6.70710318 Z" fill="#000000" fill-rule="nonzero"/>
                            <path d="M3.70710678,15.7071068 C3.31658249,16.0976311 2.68341751,16.0976311 2.29289322,15.7071068 C1.90236893,15.3165825 1.90236893,14.6834175 2.29289322,14.2928932 L8.29289322,8.29289322 C8.67147216,7.91431428 9.28105859,7.90106866 9.67572463,8.26284586 L15.6757246,13.7628459 C16.0828436,14.1360383 16.1103465,14.7686056 15.7371541,15.1757246 C15.3639617,15.5828436 14.7313944,15.6103465 14.3242754,15.2371541 L9.03007575,10.3841378 L3.70710678,15.7071068 Z" fill="#000000" fill-rule="nonzero" opacity="0.5" transform="translate(9.000003, 11.999999) rotate(-270.000000) translate(-9.000003, -11.999999) "/>
                        </g>
                    </svg></span>
                    <!--end::Svg Icon-->
                </a>
                @else
                <a href="{{ url('/auth').'/'.Request::segment(2).'/'.Request::segment(3) }}" class="text-primary" data-kt-menu-trigger="click" data-kt-menu-placement="bottom-end" data-kt-menu-flip="top-end">
                    <!--begin::Svg Icon | path: assets/media/icons/duotone/Navigation/Angle-double-left.svg-->
                    <span class="svg-icon svg-icon-5 svg-icon-gray-500 me-1"><svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" width="24px" height="24px" viewBox="0 0 24 24" version="1.1">
                        <g stroke="none" stroke-width="1" fill="none" fill-rule="evenodd">
                            <polygon points="0 0 24 0 24 24 0 24"/>
                            <path d="M5.29288961,6.70710318 C4.90236532,6.31657888 4.90236532,5.68341391 5.29288961,5.29288961 C5.68341391,4.90236532 6.31657888,4.90236532 6.70710318,5.29288961 L12.7071032,11.2928896 C13.0856821,11.6714686 13.0989277,12.281055 12.7371505,12.675721 L7.23715054,18.675721 C6.86395813,19.08284 6.23139076,19.1103429 5.82427177,18.7371505 C5.41715278,18.3639581 5.38964985,17.7313908 5.76284226,17.3242718 L10.6158586,12.0300721 L5.29288961,6.70710318 Z" fill="#000000" fill-rule="nonzero" transform="translate(8.999997, 11.999999) scale(-1, 1) translate(-8.999997, -11.999999) "/>
                            <path d="M10.7071009,15.7071068 C10.3165766,16.0976311 9.68341162,16.0976311 9.29288733,15.7071068 C8.90236304,15.3165825 8.90236304,14.6834175 9.29288733,14.2928932 L15.2928873,8.29289322 C15.6714663,7.91431428 16.2810527,7.90106866 16.6757187,8.26284586 L22.6757187,13.7628459 C23.0828377,14.1360383 23.1103407,14.7686056 22.7371482,15.1757246 C22.3639558,15.5828436 21.7313885,15.6103465 21.3242695,15.2371541 L16.0300699,10.3841378 L10.7071009,15.7071068 Z" fill="#000000" fill-rule="nonzero" opacity="0.5" transform="translate(15.999997, 11.999999) scale(-1, 1) rotate(-270.000000) translate(-15.999997, -11.999999) "/>
                        </g>
                    </svg></span>
                    <!--end::Svg Icon-->    
                     Move to Active Records
                </a>
                @endif
            @endif
        </div>
        <!--end::Actions-->
    </div>
    <!--end::Container-->
</div>
<!--end::Toolbar-->