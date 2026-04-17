<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<!-- BEGIN: Head-->
<head>

    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=0, minimal-ui">
    <meta name="description" content="Stack admin is super flexible, powerful, clean &amp; modern responsive bootstrap 4 admin template with unlimited possibilities.">
    <meta name="keywords" content="admin template, stack admin template, dashboard template, flat admin template, responsive admin template, web app">
    <meta name="author" content="PIXINVENT">
    @hasSection('seo-tags')
        @yield('seo-tags')
    @endif
    <!-- Bootstrap CSS -->
	<link
	rel="stylesheet"
	href="https://stackpath.bootstrapcdn.com/bootstrap/4.4.1/css/bootstrap.min.css"
	>
	<!-- END: Page CSS-->
	<!-- Select2 CSS -->
	<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <!-- BEGIN: Custom CSS-->
    <!--<link rel="stylesheet" type="text/css" href="{{asset('/assets/css/style.css')}}">-->
	<style>
	  /* Allow dropdown menu to auto-size based on its content */
	  .dropdown-menu {
		min-width: auto !important;
		white-space: nowrap; 
		padding:5px;
		width: auto !important;
	  }
	</style>
    <!-- END: Custom CSS-->
    @stack('stylesheets')
</head>
<!-- END: Head-->

<!-- BEGIN: Body-->
<body class="">
    
	@yield('content')
	<!-- jQuery -->
	<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>

	<!-- Popper.js (required for Bootstrap 4 tooltips & popovers) -->
	<script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.0/dist/umd/popper.min.js"></script>

	<!-- Bootstrap JS -->
	<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.4.1/js/bootstrap.min.js"></script>
	<!-- Select2 JS -->
	<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
	<script>
  $(document).ready(function() {
    $('.select2').select2({
      placeholder: "-- Select Customer --",
      allowClear: true
    });
  });
</script>
    @stack('scripts')
    <script type="text/javascript" src="{{env('CDN_DOMAIN')}}/js/manifest.js"></script>
    <script type="text/javascript" src="{{env('CDN_DOMAIN')}}/js/vendor.js"></script>
    <script type="text/javascript" src="{{env('CDN_DOMAIN')}}/js/app.js"></script>
    <!-- <script src="{{asset('/assets/js/jquery.dataTables.min.js')}}"></script> -->

</body>
<!-- END: Body-->

</html>
