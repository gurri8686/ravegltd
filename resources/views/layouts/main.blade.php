<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<!-- BEGIN: Head-->
<head>
	<?php
	$menuStateClass = $_COOKIE['menu_state'] ?? 'menu-expanded';
	?>
    <script>/* Theme color restore — runs before CSS paints to avoid flash */
    (function(){var s=localStorage.getItem('themeColors');if(s){var c=JSON.parse(s);Object.keys(c).forEach(function(k){document.documentElement.style.setProperty(k,c[k]);});}})();
    </script>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=0, minimal-ui, interactive-widget=resizes-visual">
    <meta name="description" content="Stack admin is super flexible, powerful, clean &amp; modern responsive bootstrap 4 admin template with unlimited possibilities.">
    <meta name="keywords" content="admin template, stack admin template, dashboard template, flat admin template, responsive admin template, web app">
    <meta name="author" content="PIXINVENT">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    @hasSection('seo-tags')
        @yield('seo-tags')
    @endif
    <link rel="apple-touch-icon" href="{{asset('/app-assets/images/ico/apple-icon-120.png')}}">
    <link rel="shortcut icon" type="image/x-icon" href="{{asset('/app-assets/images/ico/favicon.ico')}}">
    <link href="https://fonts.googleapis.com/css?family=Montserrat:300,300i,400,400i,500,500i%7COpen+Sans:300,300i,400,400i,600,600i,700,700i" rel="stylesheet">
    <!-- BEGIN: Vendor CSS-->
    <link rel="stylesheet" type="text/css" href="{{asset('/app-assets/vendors/css/vendors.min.css')}}">
    <link rel="stylesheet" type="text/css" href="{{asset('/app-assets/vendors/css/forms/icheck/icheck.css')}}">
    <link rel="stylesheet" type="text/css" href="{{asset('/app-assets/vendors/css/forms/icheck/custom.css')}}">
    <link rel="stylesheet" type="text/css" href="{{asset('/app-assets/vendors/css/extensions/unslider.css')}}">
    <link rel="stylesheet" type="text/css" href="{{asset('/app-assets/vendors/css/weather-icons/climacons.min.css')}}">
    <link rel="stylesheet" type="text/css" href="{{asset('/app-assets/fonts/meteocons/style.css')}}">
    <link rel="stylesheet" type="text/css" href="{{asset('/app-assets/vendors/css/charts/morris.css')}}">
    <!-- CSS-->
    <!-- BEGIN: Theme CSS-->
    <link rel="stylesheet" type="text/css" href="{{asset('/app-assets/css/bootstrap.css')}}">
    <link rel="stylesheet" type="text/css" href="{{asset('/app-assets/css/bootstrap-extended.css')}}">
    <link rel="stylesheet" type="text/css" href="{{asset('/app-assets/css/colors.css')}}">
    <link rel="stylesheet" type="text/css" href="{{asset('/app-assets/css/components.css')}}">
    <link rel="stylesheet" type="text/css" href="{{asset('/app-assets/css/pages/page-users.css')}}">
    <!-- END: Theme CSS-->
    <!-- BEGIN: Page CSS-->
    <link rel="stylesheet" type="text/css" href="{{asset('/app-assets/css/core/menu/menu-types/vertical-menu.css')}}">
    <link rel="stylesheet" type="text/css" href="{{asset('/app-assets/css/core/colors/palette-gradient.css')}}">
    <link rel="stylesheet" type="text/css" href="{{asset('/app-assets/fonts/simple-line-icons/style.css')}}">
    <link rel="stylesheet" type="text/css" href="{{asset('/app-assets/css/pages/timeline.css')}}">
    <link rel="stylesheet" type="text/css" href="{{asset('/app-assets/vendors/css/tables/datatable/datatables.min.css')}}">
    <link rel="stylesheet" type="text/css" href="{{asset('/assets/css/dataTables.dateTime.min.css')}}">
    <link rel="stylesheet" type="text/css" href="{{asset('/app-assets/css/plugins/forms/checkboxes-radios.css')}}">
    <link rel="stylesheet" type="text/css" href="{{asset('/app-assets/vendors/css/forms/toggle/switchery.min.css')}}">
	<link rel="stylesheet" type="text/css" href="{{asset('/app-assets/css/plugins/forms/switch.css')}}">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.6-rc.0/css/select2.min.css" rel="stylesheet" />
    <style>
    /* ── DataTable pagination dropdown — modern style ── */
    .rdt_Pagination select {
        appearance: none !important;
        -webkit-appearance: none !important;
        background-color: #fff !important;
        border: 1.5px solid #e2e8f0 !important;
        border-radius: 10px !important;
        padding: 0 32px 0 12px !important;
        height: 34px !important;
        font-size: 12px !important;
        font-weight: 600 !important;
        color: #374151 !important;
        cursor: pointer !important;
        outline: none !important;
        background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='10' height='10' viewBox='0 0 24 24' fill='none' stroke='%23F27420' stroke-width='3' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpolyline points='6 9 12 15 18 9'%3E%3C/polyline%3E%3C/svg%3E") !important;
        background-repeat: no-repeat !important;
        background-position: right 10px center !important;
        transition: border-color 0.15s, box-shadow 0.15s !important;
        box-shadow: 0 1px 4px rgba(0,0,0,0.06) !important;
    }
    .rdt_Pagination select:focus {
        border-color: #F27420 !important;
        box-shadow: 0 0 0 3px rgba(242,116,32,0.1) !important;
    }
    .rdt_Pagination select:hover {
        border-color: #F27420 !important;
    }
    .rdt_Pagination select option {
        font-size: 13px !important;
        font-weight: 500 !important;
        color: #374151 !important;
        padding: 8px !important;
    }
    /* ── Font size toggle buttons ── */
    .ts-font-btn { display:flex !important; align-items:center !important; justify-content:center !important; flex-direction:row !important; padding:0 !important; line-height:1 !important; box-sizing:border-box !important; }
    /* ── Hide hamburger menu icon — shown on mobile via media query ── */
    .nav-item.mobile-menu { display: none !important; }
    @media (max-width: 430px) {
        .header-navbar .navbar-header .navbar-brand { left: unset !important; position: static !important; }
    }
    @media (max-width: 430px) {
        /* Hide ellipsis toggle li entirely */
        .nav-item .open-navbar-container { display: none !important; }
        .navbar-header .navbar-nav.flex-row > li:last-child { display: none !important; }
        /* 3-column grid: hamburger | brand (center) | sale btn */
        .navbar-header .navbar-nav.flex-row {
            display: grid !important;
            grid-template-columns: auto 1fr auto !important;
            align-items: center !important;
            width: 100% !important;
            padding: 0 8px !important;
            column-gap: 10px !important;
        }
        /* Hamburger: column 1, left-aligned */
        .nav-item.mobile-menu { display: flex !important; align-items: center !important; justify-self: start !important; margin: 0 !important; grid-column: 1 !important; }
        .nav-item.mobile-menu a.nav-link { padding: 6px 4px !important; line-height: 1 !important; }
        .nav-item.mobile-menu .feather { color: black !important; }
        /* Brand: column 2, centered */
        #navbar-brand-li { display: flex !important; align-items: center !important; justify-content: center !important; justify-self: center !important; grid-column: 2 !important; position: static !important; transform: none !important; margin: 0 !important; padding: 0 !important; }
        #navbar-brand-li .navbar-brand { display: flex !important; align-items: center !important; justify-content: center !important; padding: 0 !important; margin: 0 !important; white-space: nowrap !important; }
        /* Force brand-text visible — kill theme fadeout */
        #navbar-brand-li .brand-text,
        body.menu-collapsed #navbar-brand-li .brand-text,
        body.menu-expanded #navbar-brand-li .brand-text { display: block !important; visibility: visible !important; opacity: 1 !important; animation: none !important; -webkit-animation: none !important; white-space: nowrap !important; font-size: 13px !important; margin: -37px !important; }
        #navbar-brand-li .brand-text-mini { display: none !important; }
        #navbar-brand-li img { max-width: 100% !important; height: auto !important; }
        /* Sale btn: column 3, right-aligned */
        #mobile-sale-btn { display: flex !important; align-items: center !important; align-self: center !important; justify-self: end !important; grid-column: 3 !important; margin: 0 !important; }
        #mobile-sale-btn a { display: inline-flex !important; align-items: center !important; justify-content: center !important; }
        #mobile-sale-btn a i { margin: 0 !important; padding: 0 !important; line-height: 1 !important; }
    }
    /* ── Tablet (431px–1399px): Sale button — show "Sale" only, mobile pill style ── */
    @media (min-width: 431px) and (max-width: 1399.98px) {
        .ts-order-text { display: none !important; }
        #tablet-sale-order-fix .btn-primary,
        #tablet-sale-order-fix .btn-primary:hover,
        #tablet-sale-order-fix .btn-primary:focus {
            height: 37px !important; padding: 0 14px !important; border-radius: 8px !important;
            font-size: 12px !important; font-weight: 700 !important;
            background: linear-gradient(135deg,#f97316,#ea580c) !important;
            box-shadow: 0 2px 8px rgba(249,115,22,0.3) !important;
        }
        #tablet-sale-order-fix { margin-top: 10px !important; }
    }
    /* ── Tablet portrait (431px+): force desktop navbar layout ── */
    @media (min-width: 431px) and (max-width: 767.98px) {
        /* Hide all mobile-only elements */
        #mobile-sale-btn, #mobile-sale-btn * { display: none !important; visibility: hidden !important; width: 0 !important; height: 0 !important; overflow: hidden !important; padding: 0 !important; margin: 0 !important; }
        .navbar-header .navbar-nav.flex-row > li.d-md-none { display: none !important; }
        /* Force navbar-collapse visible like desktop */
        #navbar-mobile { display: flex !important; flex-basis: auto !important; }
        /* Show desktop nav items */
        #tablet-sale-order-fix { display: flex !important; }
        #tablet-font-fix { display: flex !important; }
        .navbar-container .nav .nav-item.d-none.d-md-block { display: flex !important; }
        /* Fix navbar header — don't use mobile grid */
        .navbar-header { float: left !important; }
        .navbar-header .navbar-nav.flex-row { display: flex !important; align-items: center !important; }
    }
    /* ── Print: hide chrome, show only content ── */
    @media print {
        .header-navbar,
        .main-menu,
        .menu-expanded .main-menu,
        .sidenav-overlay,
        .drag-target,
        .navbar-header,
        .footer,
        .no-print { display: none !important; }
        .app-content,
        .content-wrapper,
        .main-panel { margin: 0 !important; padding: 0 !important; width: 100% !important; }
        body { background: #fff !important; }
        -webkit-print-color-adjust: exact;
        print-color-adjust: exact;
    }
    /* ── Toast notifications: card UI (white card, colored left bar, circle icon, close) — always above navbar ── */
    .Toastify__toast-container { z-index: 999999 !important; width: 360px !important; padding: 0 !important; }
    .Toastify__toast-container--top-right { top: 68px !important; right: 16px !important; }
    .Toastify__toast {
        position: relative !important;
        display: flex !important;
        align-items: center !important;
        gap: 12px !important;
        min-height: 64px !important;
        padding: 14px 38px 14px 18px !important;
        margin-bottom: 12px !important;
        background: #fff !important;
        color: #0f172a !important;
        border: none !important;
        border-radius: 12px !important;
        box-shadow: 0 12px 32px rgba(15,23,42,0.18) !important;
        overflow: hidden !important;
        font-family: inherit !important;
    }
    /* colored left accent bar */
    .Toastify__toast::before { content: '' !important; position: absolute !important; left: 0 !important; top: 0 !important; bottom: 0 !important; width: 5px !important; }
    .Toastify__toast--success::before { background: #22c55e !important; }
    .Toastify__toast--error::before   { background: #ef4444 !important; }
    .Toastify__toast--warning::before { background: #f59e0b !important; }
    .Toastify__toast--info::before,
    .Toastify__toast--default::before { background: #F27420 !important; }
    /* message text */
    .Toastify__toast-body { margin: 0 !important; padding: 0 !important; font-size: 13.5px !important; font-weight: 600 !important; color: #0f172a !important; line-height: 1.35 !important; align-items: center !important; }
    /* built-in type icon → circular badge */
    .Toastify__toast-icon { width: 34px !important; height: 34px !important; border-radius: 50% !important; display: inline-flex !important; align-items: center !important; justify-content: center !important; margin-inline-end: 0 !important; flex-shrink: 0 !important; }
    .Toastify__toast--success .Toastify__toast-icon { background: #22c55e !important; }
    .Toastify__toast--error   .Toastify__toast-icon { background: #ef4444 !important; }
    .Toastify__toast--warning .Toastify__toast-icon { background: #f59e0b !important; }
    .Toastify__toast--info    .Toastify__toast-icon,
    .Toastify__toast--default .Toastify__toast-icon { background: #F27420 !important; }
    .Toastify__toast-icon svg { fill: #fff !important; width: 17px !important; height: 17px !important; }
    /* progress bar tinted per type */
    .Toastify__progress-bar--success { background: #22c55e !important; }
    .Toastify__progress-bar--error   { background: #ef4444 !important; }
    .Toastify__progress-bar--warning { background: #f59e0b !important; }
    .Toastify__progress-bar--info,
    .Toastify__progress-bar--default { background: #F27420 !important; }
    .Toastify__close-button { position: absolute !important; top: 10px !important; right: 10px !important; background: none !important; border: none !important; cursor: pointer !important; opacity: 0.45 !important; font-size: 16px !important; color: #64748b !important; padding: 2px !important; line-height: 1 !important; align-self: flex-start !important; }
    .Toastify__close-button:hover { opacity: 1 !important; color: #0f172a !important; }
    /* ── Mobile z-index: sidebar drawer above navbar ── */
    @media (max-width: 767px) {
        .header-navbar { z-index: 1000 !important; }
        .Toastify__toast-container { width: auto !important; }
        .Toastify__toast-container--top-right { top: 56px !important; right: 8px !important; left: 8px !important; }
        .ph-table-wrap { overflow-x: scroll !important; -webkit-overflow-scrolling: touch !important; }
        .rdt_TableCell, .rdt_TableCol { padding: 0 8px !important; }
        .rdt_TableCell { font-size: 12px !important; }
        /* ── Mobile sidebar: scrollable with fixed logout at bottom ── */
        .main-menu {
            display: flex !important;
            flex-direction: column !important;
            height: 100vh !important;
            height: 100dvh !important;
        }
        .main-menu .main-menu-content {
            flex: 1 !important;
            overflow-y: auto !important;
            overflow-x: hidden !important;
            -webkit-overflow-scrolling: touch !important;
        }
        .main-menu .sidebar-logout {
            flex-shrink: 0 !important;
            border-top: 1px solid #f1f5f9 !important;
        }
    }
    /* ══════════════════════════════════════════════
       Tablet Sidebar — Professional Slim Bar + Tooltips
       ══════════════════════════════════════════════ */
    @media (min-width: 768px) and (max-width: 1024px) {

        /* ══ COLLAPSED: strict 75px ══ */
        body.vertical-layout.vertical-menu.menu-collapsed .main-menu,
        body.vertical-layout.vertical-menu.menu-collapsed .navbar .navbar-header {
            width: 75px !important;
            min-width: 75px !important;
            max-width: 75px !important;
            overflow: visible !important;
            transition: width 0.3s ease !important;
        }
        /* ── Content: starts exactly at 75px ── */
        body.vertical-layout.vertical-menu.menu-collapsed .app-content,
        body.vertical-layout.vertical-menu.menu-collapsed .content,
        body.vertical-layout.vertical-menu.menu-collapsed .footer {
            margin-left: 75px !important;
            transition: margin-left 0.3s ease !important;
        }

        /* ── Sidebar shell: white, shadow ── */
        .main-menu {
            background: #ffffff !important;
            border-right: 1px solid #f1f5f9 !important;
            box-shadow: 2px 0 20px rgba(0,0,0,0.07) !important;
            transition: width 0.3s ease !important;
        }
        .main-menu .main-menu-content,
        .main-menu .navigation { background: #ffffff !important; }

        /* ── Clamp nav list width ── */
        body.vertical-layout.vertical-menu.menu-collapsed .main-menu .main-menu-content {
            padding-top: 10px !important;
            width: 75px !important;
            overflow: visible !important;
        }
        body.vertical-layout.vertical-menu.menu-collapsed .main-menu .navigation {
            width: 75px !important;
            overflow: visible !important;
            padding: 0 !important;
            margin: 0 !important;
        }

        /* ── Brand logo — orange gradient, centered pill ── */
        .sidebar-brand-logo {
            display: flex !important; align-items: center !important; justify-content: center !important;
            height: 60px !important; min-height: 60px !important;
            background: linear-gradient(135deg,#f97316,#ea580c) !important;
            border-bottom: none !important; flex-shrink: 0 !important; overflow: hidden !important;
        }
        .sidebar-brand-logo a {
            display: flex !important; align-items: center !important; justify-content: center !important;
            width: 40px !important; height: 40px !important;
            background: rgba(255,255,255,0.18) !important; border-radius: 11px !important;
            text-decoration: none !important;
        }
        .sidebar-brand-logo img {
            max-height: 24px !important; max-width: 24px !important;
            object-fit: contain !important; filter: brightness(0) invert(1) !important;
        }

        /* ══ EXPANDED: sidebar 240px, content starts at 240px ══ */
        body.vertical-layout.vertical-menu.menu-expanded .main-menu,
        body.vertical-layout.vertical-menu.menu-expanded .navbar .navbar-header {
            width: 240px !important;
            min-width: 240px !important;
            transition: width 0.3s ease !important;
            overflow-x: hidden !important;
        }
        body.vertical-layout.vertical-menu.menu-expanded .app-content,
        body.vertical-layout.vertical-menu.menu-expanded .content,
        body.vertical-layout.vertical-menu.menu-expanded .footer {
            margin-left: 240px !important;
            transition: margin-left 0.3s ease !important;
        }
        body.vertical-layout.vertical-menu.menu-expanded .main-menu .main-menu-content {
            padding-top: 10px !important;
            overflow-y: auto !important;
            overflow-x: hidden !important;
        }

        /* ══ COLLAPSED: completely remove section headers ══ */
        body.vertical-layout.vertical-menu.menu-collapsed .main-menu .navigation > li.navigation-header {
            display: none !important;
        }

        /* ── Nav item: full 75px width, flex column (icon + label stacked) ── */
        body.vertical-layout.vertical-menu.menu-collapsed .main-menu .navigation > li:not(.navigation-header) {
            width: 75px !important;
            display: flex !important;
            justify-content: center !important;
            align-items: center !important;
            padding: 0 !important;
            margin: 2px 0 !important;
            overflow: visible !important;
        }

        /* ── Nav link: full 75px wide, column layout — icon on top, mini label below ── */
        body.vertical-layout.vertical-menu.menu-collapsed .main-menu .navigation > li:not(.navigation-header) > a {
            display: flex !important;
            flex-direction: column !important;
            align-items: center !important;
            justify-content: center !important;
            width: 75px !important;
            min-width: 75px !important;
            height: 56px !important;
            min-height: 56px !important;
            padding: 0 !important;
            margin: 0 !important;
            border-radius: 0 !important;
            background: transparent !important;
            box-shadow: none !important;
            border: none !important;
            text-decoration: none !important;
            overflow: visible !important;
            transition: background 0.2s ease !important;
            gap: 4px !important;
        }

        /* ── Icons: 20px centered ── */
        body.vertical-layout.vertical-menu.menu-collapsed .main-menu .navigation > li:not(.navigation-header) > a > i {
            font-size: 20px !important;
            line-height: 1 !important;
            margin: 0 !important;
            margin-right: 0 !important;
            padding: 0 !important;
            color: #94a3b8 !important;
            display: block !important;
            text-align: center !important;
            float: none !important;
            position: static !important;
            flex-shrink: 0 !important;
            transition: color 0.2s ease !important;
        }

        /* ══ MINI LABEL: always visible below icon ══ */
        body.vertical-layout.vertical-menu.menu-collapsed .main-menu .navigation > li:not(.navigation-header) > a > span.menu-title {
            display: block !important;
            visibility: visible !important;
            opacity: 1 !important;
            position: static !important;
            clip: auto !important;
            width: 75px !important;
            max-width: 75px !important;
            height: auto !important;
            max-height: none !important;
            font-size: 9px !important;
            font-weight: 600 !important;
            color: #94a3b8 !important;
            text-align: center !important;
            white-space: nowrap !important;
            overflow: hidden !important;
            text-overflow: ellipsis !important;
            padding: 0 4px !important;
            margin: 0 !important;
            line-height: 1.2 !important;
            letter-spacing: 0.2px !important;
            pointer-events: none !important;
        }

        /* ══ Fly-out submenu — reposition from vendor's 60px to our 75px ══ */
        body.vertical-layout.vertical-menu.menu-collapsed .main-menu .navigation > li > ul.menu-content,
        body.vertical-layout.vertical-menu.menu-collapsed .main-menu .main-menu-content > ul.menu-content {
            left: 75px !important;
            width: 210px !important;
            background: #fff !important;
            border-radius: 12px !important;
            box-shadow: 0 8px 28px rgba(0,0,0,0.12) !important;
            border: 1px solid #f1f5f9 !important;
            padding: 6px 0 !important;
        }
        body.vertical-layout.vertical-menu.menu-collapsed .main-menu .navigation > li > ul.menu-content li a {
            padding: 9px 16px !important;
            font-size: 13px !important; color: #374151 !important;
            border-radius: 0 !important;
            display: flex !important; align-items: center !important; gap: 8px !important;
        }
        body.vertical-layout.vertical-menu.menu-collapsed .main-menu .navigation > li > ul.menu-content li a:hover {
            background: #fff7ed !important; color: #f97316 !important;
        }

        /* ── Hover: tinted bg ── */
        body.vertical-layout.vertical-menu.menu-collapsed .main-menu .navigation > li:not(.navigation-header):hover > a {
            background: #fff7ed !important;
        }
        body.vertical-layout.vertical-menu.menu-collapsed .main-menu .navigation > li:not(.navigation-header):hover > a > i {
            color: #f97316 !important;
        }
        body.vertical-layout.vertical-menu.menu-collapsed .main-menu .navigation > li:not(.navigation-header):hover > a > span.menu-title {
            color: #f97316 !important;
        }

        /* ── Active: left orange bar ── */
        body.vertical-layout.vertical-menu.menu-collapsed .main-menu .navigation > li.active:not(.navigation-header) > a {
            background: linear-gradient(135deg, rgb(254,252,232), rgb(254,249,195)) !important;
            box-shadow: inset 3px 0 0 #f97316 !important;
        }
        body.vertical-layout.vertical-menu.menu-collapsed .main-menu .navigation > li.active:not(.navigation-header) > a > i {
            color: #f97316 !important;
        }
        body.vertical-layout.vertical-menu.menu-collapsed .main-menu .navigation > li.active:not(.navigation-header) > a > span.menu-title {
            color: #f97316 !important;
        }

        /* ══ EXPANDED: section header dividers ══ */
        body.vertical-layout.vertical-menu.menu-expanded .main-menu .navigation > li.navigation-header {
            font-size: 0 !important;
            padding: 18px 16px 6px 16px !important;
            margin: 0 !important;
            overflow: hidden !important;
        }
        body.vertical-layout.vertical-menu.menu-expanded .main-menu .navigation > li.navigation-header:first-child {
            padding-top: 10px !important;
        }
        body.vertical-layout.vertical-menu.menu-expanded .main-menu .navigation > li.navigation-header span {
            display: block !important;
            font-size: 10px !important;
            letter-spacing: 1.4px !important;
            font-weight: 700 !important;
            color: #94a3b8 !important;
            text-transform: uppercase !important;
            line-height: 1 !important;
        }
        body.vertical-layout.vertical-menu.menu-expanded .main-menu .navigation > li.navigation-header .feather { display: none !important; }

        /* ══ EXPANDED: nav item wrapper ══ */
        body.vertical-layout.vertical-menu.menu-expanded .main-menu .navigation > li:not(.navigation-header) {
            margin: 1px 8px !important;
            border-radius: 10px !important;
            overflow: hidden !important;
        }

        /* ══ EXPANDED: nav link — icon + label ══ */
        body.vertical-layout.vertical-menu.menu-expanded .main-menu .navigation > li:not(.navigation-header) > a {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif !important;
            font-size: 13.5px !important;
            font-weight: 500 !important;
            color: #374151 !important;
            min-height: 44px !important;
            display: flex !important;
            align-items: center !important;
            padding: 0 14px !important;
            border-radius: 10px !important;
            transition: all 0.2s ease !important;
            position: relative !important;
            letter-spacing: 0.1px !important;
            background: transparent !important;
            border: none !important;
            box-shadow: none !important;
            text-decoration: none !important;
        }
        body.vertical-layout.vertical-menu.menu-expanded .main-menu .navigation > li:not(.navigation-header) > a > i {
            font-size: 16px !important;
            margin-right: 11px !important;
            margin-left: 0 !important;
            color: #94a3b8 !important;
            flex-shrink: 0 !important;
            width: 20px !important;
            text-align: center !important;
            transition: color 0.2s ease !important;
            float: none !important;
            position: static !important;
        }
        body.vertical-layout.vertical-menu.menu-expanded .main-menu .navigation > li:not(.navigation-header) > a > span.menu-title {
            display: block !important;
            visibility: visible !important;
            opacity: 1 !important;
            width: auto !important;
            max-width: none !important;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif !important;
            font-size: 13.5px !important;
            font-weight: 500 !important;
            color: #374151 !important;
            letter-spacing: 0.1px !important;
            white-space: nowrap !important;
            overflow: hidden !important;
            text-overflow: ellipsis !important;
            position: static !important;
            clip: auto !important;
        }

        /* ══ EXPANDED: hover state ══ */
        body.vertical-layout.vertical-menu.menu-expanded .main-menu .navigation > li:not(.navigation-header):hover > a {
            background: #fff7ed !important; color: #f97316 !important;
        }
        body.vertical-layout.vertical-menu.menu-expanded .main-menu .navigation > li:not(.navigation-header):hover > a > i { color: #f97316 !important; }
        body.vertical-layout.vertical-menu.menu-expanded .main-menu .navigation > li:not(.navigation-header):hover > a > span.menu-title { color: #f97316 !important; }

        /* ══ EXPANDED: active state ══ */
        body.vertical-layout.vertical-menu.menu-expanded .main-menu .navigation > li.active:not(.navigation-header) > a {
            background: linear-gradient(135deg, rgb(254,252,232), rgb(254,249,195)) !important;
            box-shadow: inset 3px 0 0 #f97316 !important;
            color: #f97316 !important;
            font-weight: 700 !important;
        }
        body.vertical-layout.vertical-menu.menu-expanded .main-menu .navigation > li.active:not(.navigation-header) > a > i { color: #f97316 !important; }
        body.vertical-layout.vertical-menu.menu-expanded .main-menu .navigation > li.active:not(.navigation-header) > a > span.menu-title { color: #f97316 !important; }

        /* ══ EXPANDED: dropdown submenu ══ */
        body.vertical-layout.vertical-menu.menu-expanded .main-menu .navigation > li.has-sub > ul.menu-content {
            background: #f8fafc !important;
            border-top: none !important;
            padding: 4px 0 !important;
        }
        body.vertical-layout.vertical-menu.menu-expanded .main-menu .navigation > li.has-sub > ul.menu-content li {
            margin: 0 !important;
        }
        body.vertical-layout.vertical-menu.menu-expanded .main-menu .navigation > li.has-sub > ul.menu-content li a {
            display: flex !important;
            align-items: center !important;
            gap: 8px !important;
            padding: 9px 14px 9px 44px !important;
            font-size: 12px !important;
            color: #6b7280 !important;
            font-weight: 500 !important;
            border-radius: 0 !important;
            transition: all 0.15s ease !important;
        }
        body.vertical-layout.vertical-menu.menu-expanded .main-menu .navigation > li.has-sub > ul.menu-content li a:hover {
            background: #fff7ed !important; color: #f97316 !important;
        }
        body.vertical-layout.vertical-menu.menu-expanded .main-menu .navigation > li.has-sub > ul.menu-content li.active a {
            color: #f97316 !important; font-weight: 600 !important; background: #fff7ed !important;
        }
    }
    /* ── Collapsed sidebar: small top padding (all screen sizes) ── */
    body.vertical-layout.vertical-menu.menu-collapsed .main-menu .main-menu-content {
        padding-top: 10px !important;
    }
    /* ══ Collapsed sidebar fly-out popover ══ */
    #csFlyout {
        position: fixed;
        left: 75px;
        z-index: 99999;
        background: #fff;
        border-radius: 14px;
        box-shadow: 0 8px 32px rgba(0,0,0,0.16), 0 2px 8px rgba(0,0,0,0.08);
        border: 1px solid #f1f5f9;
        min-width: 200px;
        max-width: 240px;
        padding: 6px 0 8px;
        display: none;
        overflow: hidden;
    }
    #csFlyout .csf-title {
        font-size: 10px;
        font-weight: 800;
        color: #94a3b8;
        letter-spacing: 1.2px;
        text-transform: uppercase;
        padding: 8px 16px 6px;
        border-bottom: 1px solid #f1f5f9;
        margin-bottom: 4px;
    }
    #csFlyout .csf-item {
        display: flex;
        align-items: center;
        gap: 10px;
        padding: 10px 16px;
        font-size: 13px;
        font-weight: 500;
        color: #374151;
        text-decoration: none;
        transition: background 0.15s, color 0.15s;
        line-height: 1;
    }
    #csFlyout .csf-item i {
        font-size: 13px;
        color: #94a3b8;
        width: 16px;
        text-align: center;
        flex-shrink: 0;
        transition: color 0.15s;
    }
    #csFlyout .csf-item:hover,
    #csFlyout .csf-item:active { background: #fff7ed !important; color: #f97316 !important; }
    #csFlyout .csf-item:hover i,
    #csFlyout .csf-item:active i { color: #f97316 !important; }
    body.menu-collapsed .main-menu .navigation > li.cs-flyout-open > a {
        background: #fff3e6 !important;
        box-shadow: inset 3px 0 0 #f97316 !important;
    }
    body.menu-collapsed .main-menu .navigation > li.cs-flyout-open > a > i { color: #f97316 !important; }
    body.menu-collapsed .main-menu .navigation > li.cs-flyout-open > a > span.menu-title { color: #f97316 !important; }
    /* ── Hide Roles/Users from Management sidebar - moved to Settings page ── */
    .navigation a.menu-item[href*="management.roles"] { display: none !important; }
    .navigation a.menu-item[href*="management.users"] { display: none !important; }
    .navigation li.nav-item a.menu-item[href*="management.roles"] + ul { display: none !important; }
    /* ── Global Modern Buttons ── */
    .btn-primary, .btn-primary:hover, .btn-primary:focus, .btn-primary:active, .btn-primary:not(:disabled):not(.disabled):active {
        background: #F27420 !important; border-color: #F27420 !important; color: #fff !important;
        border-radius: 12px !important; font-weight: 600 !important; font-size: 13.5px !important;
        box-shadow: 0 2px 8px rgba(242,116,32,0.3) !important; outline: none !important;
        height: 44px; display: inline-flex; align-items: center; padding: 0 24px;
    }
    .btn-primary:hover { background: #e0600e !important; }
    .btn-primary:focus { box-shadow: 0 0 0 4px rgba(242,116,32,0.15) !important; }
    /* ── Form action buttons (Cancel + Save) — global ── */
    .form-actions {
        display: flex !important; align-items: center !important; justify-content: flex-end !important;
        gap: 12px !important; padding-top: 20px !important; border-top: 1px solid #f1f5f9 !important; margin-top: 20px !important;
    }
    /* Cancel link */
    .form-actions a {
        display: inline-flex !important; align-items: center !important; gap: 6px !important;
        height: 42px !important; padding: 0 24px !important; border-radius: 10px !important;
        border: 2px solid #e2e8f0 !important; background: #fff !important; color: #64748b !important;
        font-size: 14px !important; font-weight: 600 !important; text-decoration: none !important;
        transition: border-color 0.15s, color 0.15s !important; outline: none !important;
    }
    .form-actions a:hover { border-color: #f97316 !important; color: #f97316 !important; }
    /* Cancel icon always black */
    a .fa-times, button .fa-times { color: #000 !important; }
    /* Save button */
    .form-actions .btn-primary {
        display: inline-flex !important; align-items: center !important; gap: 6px !important;
        height: 42px !important; padding: 0 28px !important; border-radius: 10px !important;
        background: linear-gradient(135deg, #f97316, #ea580c) !important;
        color: #fff !important; font-size: 14px !important; font-weight: 700 !important;
        border: none !important; box-shadow: 0 3px 10px rgba(249,115,22,0.3) !important;
        cursor: pointer !important; outline: none !important;
    }
    .form-actions .btn-primary:hover { opacity: 0.92 !important; box-shadow: 0 4px 14px rgba(249,115,22,0.4) !important; }
    /* ── Global Orange Dropdown Hover ── */
    /* Native select */
    select option:checked { background-color: #F27420 !important; color: #fff !important; }
    select option:hover { background-color: #F27420 !important; color: #fff !important; }
    select:focus { border-color: #F27420 !important; outline: none !important; }
    .rdt_Pagination select { accent-color: #F27420; border: 1.5px solid #e2e8f0; border-radius: 8px; padding: 4px 8px; outline: none; }
    .rdt_Pagination select:focus { border-color: #F27420 !important; box-shadow: 0 0 0 3px rgba(242,116,32,0.08); }
    /* Bootstrap dropdowns */
    .dropdown-item:hover, .dropdown-item:focus, .dropdown-item:active { background-color: #FFF5ED !important; color: #F27420 !important; }
    .dropdown-item.active { background-color: #F27420 !important; color: #fff !important; }
    /* DataTables length menu */
    .dataTables_length select:focus { border-color: #F27420 !important; outline: none; }
    .dataTables_wrapper .dataTables_length select option:checked { background-color: #F27420 !important; color: #fff !important; }
    /* General focus override */
    .form-control:focus { border-color: #F27420 !important; box-shadow: 0 0 0 0.2rem rgba(242,116,32,0.15) !important; }
    .select2-container--default .select2-results__option--highlighted[aria-selected] { background-color: #F27420 !important; color: #fff !important; }
    .select2-container--default .select2-results__option--highlighted { background-color: #F27420 !important; color: #fff !important; }
    .select2-container--default .select2-results__option--highlighted.select2-results__option--selectable { background-color: #F27420 !important; color: #fff !important; }
    .select2-results__option--highlighted { background-color: #F27420 !important; color: #fff !important; }
    .select2-container--default .select2-results__option--selected { background-color: #FFF5ED !important; color: #F27420 !important; }
    .select2-container--default .select2-results__option[aria-selected=true] { background-color: #FFF5ED !important; color: #F27420 !important; }
    .select2-container--default .select2-selection--single { display: flex !important; align-items: center !important; }
    .select2-container--default .select2-selection--single .select2-selection__rendered { line-height: normal !important; padding-top: 0 !important; padding-bottom: 0 !important; flex: 1; }
    .select2-container--default .select2-selection--single .select2-selection__arrow { position: static !important; height: auto !important; display: flex; align-items: center; padding-right: 8px; }
    .select2-container--default .select2-selection--single .select2-selection__arrow b { border-color: #94a3b8 transparent transparent transparent !important; margin-top: 0 !important; position: static !important; }
    </style>
	<!-- END: Page CSS-->
    <!-- BEGIN: Custom CSS-->
    <link rel="stylesheet" type="text/css" href="{{asset('/assets/css/style.css')}}">
    <!-- END: Custom CSS-->
    <style>
    /* ── Hide breadcrumb header globally ── */
    .content-header { display: none !important; }
    /* ── Equal spacing: content-wrapper padding matches header card margin ── */
    html body .content .content-wrapper { padding-top: 18px !important; padding-bottom: 18px !important; }
    @media (max-width: 767.98px) { html body .content .content-wrapper { padding-top: 14px !important; padding-bottom: 14px !important; } }
    /* ── Desktop sidebar scroll (>1024px) ── */
    @media (min-width: 1025px) {
        .main-menu.menu-fixed .navigation-main {
            scrollbar-width: thin !important;
            scrollbar-color: #cccccc transparent !important;
        }
        .main-menu.menu-fixed .navigation-main::-webkit-scrollbar { width: 5px !important; }
        .main-menu.menu-fixed .navigation-main::-webkit-scrollbar-thumb { background: #cccccc !important; border-radius: 10px !important; }
        .main-menu.menu-fixed .navigation-main::-webkit-scrollbar-track { background: transparent !important; }
    }

    </style>
    @stack('stylesheets')
    <link rel="stylesheet" type="text/css" href="{{env('CDN_DOMAIN')}}/css/app.css?v={{time()}}">
    <style>
    /* ── Sidebar font override — loaded AFTER CDN to win cascade ── */
    body.vertical-layout.vertical-menu.menu-expanded .main-menu .navigation > li:not(.navigation-header) > a,
    /* Expanded state only — collapsed state uses its own 9px rule above. */
    body.vertical-layout.vertical-menu.menu-expanded .main-menu .navigation > li:not(.navigation-header) > a,
    body.vertical-layout.vertical-menu.menu-expanded .main-menu .navigation > li:not(.navigation-header) > a > span.menu-title,
    body.vertical-layout.vertical-menu.menu-expanded .main-menu.menu-dark .navigation > li:not(.navigation-header) > a,
    body.vertical-layout.vertical-menu.menu-expanded .main-menu.menu-dark .navigation > li:not(.navigation-header) > a > span.menu-title {
        font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif !important;
        font-size: 13.5px !important;
        font-weight: 500 !important;
        color: #374151 !important;
        letter-spacing: 0.1px !important;
    }
    </style>
</head>
<!-- END: Head-->

<!-- BEGIN: Body-->
<body class="vertical-layout vertical-menu 2-columns fixed-navbar brand {{$menuStateClass}}" data-open="click" data-menu="vertical-menu-modern" data-col="2-columns">
    <!-- BEGIN: Header-->
    <nav class="
		header-navbar navbar-expand-md navbar navbar-with-menu fixed-top navbar-semi-dark navbar-shadow">
        <div class="navbar-wrapper">
            <div class="navbar-header">
                <ul class="nav navbar-nav flex-row">
                  @php( $companydetail = \App\Models\CompanyDetailModel::first() )

                  <li class="nav-item mobile-menu d-md-none mr-auto"><a class="nav-link nav-menu-main menu-toggle hidden-xs" href="#"><i class="feather icon-menu font-large-1"></i></a></li>
                  <li class="nav-item" id="navbar-brand-li"><a class="navbar-brand" href="{{route('dashboard.view.index')}}">
                      <span class="brand-text-mini">R&A</span>
                      <h3 class="brand-text">R & A Veg Ltd</h3>
                      </a></li>


                    <li id="mobile-sale-btn" class="nav-item d-md-none" style="gap:5px;display:flex;align-items:center;">
                        <div style="position:relative;" id="ts-font-dropdown-wrap">
                            <button onclick="tsFontDropdownToggle()" id="ts-font-dropdown-btn" title="Font size" style="display:inline-flex;align-items:center;gap:4px;height:28px;padding:0 8px;border:none;background:rgba(0,0,0,0.06);border-radius:6px;cursor:pointer;color:#374151;font-weight:700;flex-shrink:0;font-size:12px;">
                                <span style="font-size:13px;line-height:1;">A</span><span id="ts-font-pct-label" style="font-size:10px;font-weight:600;color:#64748b;"></span><i class="fa fa-caret-down" style="font-size:10px;color:#94a3b8;margin-left:1px;"></i>
                            </button>
                            <div id="ts-font-dropdown-menu" style="display:none;position:absolute;top:34px;right:0;background:#fff;border-radius:10px;box-shadow:0 4px 20px rgba(0,0,0,0.15);border:1px solid #e5e7eb;padding:6px;z-index:99999;min-width:140px;">
                                <div style="display:flex;align-items:center;justify-content:space-between;padding:6px 8px;margin-bottom:4px;">
                                    <span style="font-size:11px;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:0.5px;">Font Size</span>
                                    <span id="ts-font-pct-display" style="font-size:11px;font-weight:700;color:#f97316;"></span>
                                </div>
                                <div style="display:flex;align-items:center;gap:6px;padding:4px 8px;">
                                    <button data-ts-font-dir="-1" onclick="tsAdjustFontSize(-1)" style="width:32px;height:32px;border:1.5px solid #e2e8f0;background:#f8fafc;border-radius:8px;cursor:pointer;color:#374151;font-weight:700;font-size:16px;display:inline-flex;align-items:center;justify-content:center;transition:all 0.15s;" onmouseover="this.style.borderColor='#f97316';this.style.color='#f97316'" onmouseout="this.style.borderColor='#e2e8f0';this.style.color='#374151'">−</button>
                                    <div style="flex:1;height:4px;background:#e5e7eb;border-radius:4px;position:relative;overflow:hidden;">
                                        <div id="ts-font-bar" style="height:100%;background:#f97316;border-radius:4px;transition:width 0.2s;"></div>
                                    </div>
                                    <button data-ts-font-dir="1" onclick="tsAdjustFontSize(1)" style="width:32px;height:32px;border:1.5px solid #e2e8f0;background:#f8fafc;border-radius:8px;cursor:pointer;color:#374151;font-weight:700;font-size:16px;display:inline-flex;align-items:center;justify-content:center;transition:all 0.15s;" onmouseover="this.style.borderColor='#f97316';this.style.color='#f97316'" onmouseout="this.style.borderColor='#e2e8f0';this.style.color='#374151'">+</button>
                                </div>
                                <button onclick="tsResetFontSize()" style="width:100%;margin-top:4px;height:28px;border:none;background:#f8fafc;border-radius:6px;cursor:pointer;color:#64748b;font-size:11px;font-weight:600;transition:all 0.15s;" onmouseover="this.style.background='#fff7ed';this.style.color='#f97316'" onmouseout="this.style.background='#f8fafc';this.style.color='#64748b'">Reset to 100%</button>
                            </div>
                        </div>
                        <a href="/data_entry/sales_entry/view" style="display:inline-flex;align-items:center;justify-content:center;gap:4px;height:30px;padding:0 9px;border-radius:20px;background:linear-gradient(135deg,#f97316,#ea580c);color:#fff;text-decoration:none;box-shadow:0 2px 8px rgba(249,115,22,0.3);flex-shrink:0;white-space:nowrap;">
                            <i class="fa fa-plus" style="font-size:10px;color:#fff;"></i>
                            <span style="font-size:11px;font-weight:700;color:#fff;">Sale</span>
                        </a>
                    </li>
                    <li class="nav-item d-md-none"><a class="nav-link open-navbar-container" data-toggle="collapse" data-target="#navbar-mobile"><i class="fa fa-ellipsis-v"></i></a></li>
                </ul>
            </div>
            <div class="navbar-container content">
                <div class="collapse navbar-collapse" id="navbar-mobile">
                    <ul class="nav navbar-nav mr-auto float-left">
                        <li class="nav-item d-none d-md-block"><a class="nav-link nav-menu-main menu-toggle hidden-xs" href="#"><i onclick="toggleMenuNav()" class="feather icon-menu"></i></a></li>
                    </ul>
                    <ul class="nav navbar-nav float-right">
                        <!-- Theme color picker, notifications bell, and messages mail icons - commented out
                        <li class="nav-item">
                            <a class="nav-link nav-link-label" href="#" onclick="event.preventDefault();toggleColorPicker();" title="Theme Colors">
                                <i class="ficon feather icon-sliders"></i>
                            </a>
                        </li>
						<li class="dropdown dropdown-notification nav-item"><a class="nav-link nav-link-label" href="#" data-toggle="dropdown"><i class="ficon feather icon-bell"></i><span class="badge badge-pill badge-danger badge-up">5</span></a>
                            <ul class="dropdown-menu dropdown-menu-media dropdown-menu-right">
                                <li class="dropdown-menu-header">
                                    <h6 class="dropdown-header m-0"><span class="grey darken-2">Notifications</span><span class="notification-tag badge badge-danger float-right m-0">5 New</span></h6>
                                </li>
                                <li class="scrollable-container media-list"><a href="javascript:void(0)">
                                        <div class="media">
                                            <div class="media-left align-self-center"><i class="feather icon-plus-square icon-bg-circle bg-cyan"></i></div>
                                            <div class="media-body">
                                                <h6 class="media-heading">You have new order!</h6>
                                                <p class="notification-text font-small-3 text-muted">Lorem ipsum dolor sit amet, consectetuer elit.</p><small>
                                                    <time class="media-meta text-muted" datetime="2015-06-11T18:29:20+08:00">30 minutes ago</time></small>
                                            </div>
                                        </div>
                                    </a><a href="javascript:void(0)">
                                        <div class="media">
                                            <div class="media-left align-self-center"><i class="feather icon-download-cloud icon-bg-circle bg-red bg-darken-1"></i></div>
                                            <div class="media-body">
                                                <h6 class="media-heading red darken-1">99% Server load</h6>
                                                <p class="notification-text font-small-3 text-muted">Aliquam tincidunt mauris eu risus.</p><small>
                                                    <time class="media-meta text-muted" datetime="2015-06-11T18:29:20+08:00">Five hour ago</time></small>
                                            </div>
                                        </div>
                                    </a><a href="javascript:void(0)">
                                        <div class="media">
                                            <div class="media-left align-self-center"><i class="feather icon-alert-triangle icon-bg-circle bg-yellow bg-darken-3"></i></div>
                                            <div class="media-body">
                                                <h6 class="media-heading yellow darken-3">Warning notifixation</h6>
                                                <p class="notification-text font-small-3 text-muted">Vestibulum auctor dapibus neque.</p><small>
                                                    <time class="media-meta text-muted" datetime="2015-06-11T18:29:20+08:00">Today</time></small>
                                            </div>
                                        </div>
                                    </a><a href="javascript:void(0)">
                                        <div class="media">
                                            <div class="media-left align-self-center"><i class="feather icon-check-circle icon-bg-circle bg-cyan"></i></div>
                                            <div class="media-body">
                                                <h6 class="media-heading">Complete the task</h6><small>
                                                    <time class="media-meta text-muted" datetime="2015-06-11T18:29:20+08:00">Last week</time></small>
                                            </div>
                                        </div>
                                    </a><a href="javascript:void(0)">
                                        <div class="media">
                                            <div class="media-left align-self-center"><i class="feather icon-file icon-bg-circle bg-teal"></i></div>
                                            <div class="media-body">
                                                <h6 class="media-heading">Generate monthly report</h6><small>
                                                    <time class="media-meta text-muted" datetime="2015-06-11T18:29:20+08:00">Last month</time></small>
                                            </div>
                                        </div>
                                    </a></li>
                                <li class="dropdown-menu-footer"><a class="dropdown-item text-muted text-center" href="javascript:void(0)">Read all notifications</a></li>
                            </ul>
                        </li>
                        <li class="dropdown dropdown-notification nav-item"><a class="nav-link nav-link-label" href="#" data-toggle="dropdown"><i class="ficon feather icon-mail"></i><span class="badge badge-pill badge-warning badge-up">3</span></a>
                            <ul class="dropdown-menu dropdown-menu-media dropdown-menu-right">
                                <li class="dropdown-menu-header">
                                    <h6 class="dropdown-header m-0"><span class="grey darken-2">Messages</span><span class="notification-tag badge badge-warning float-right m-0">4 New</span></h6>
                                </li>
                                <li class="scrollable-container media-list"><a href="javascript:void(0)">
                                        <div class="media">
                                            <div class="media-left">
                                                <div class="avatar avatar-online avatar-sm rounded-circle"><img src="../../../app-assets/images/portrait/small/avatar-s-1.png" alt="avatar"><i></i></div>
                                            </div>
                                            <div class="media-body">
                                                <h6 class="media-heading">Margaret Govan</h6>
                                                <p class="notification-text font-small-3 text-muted">I like your portfolio, let's start.</p><small>
                                                    <time class="media-meta text-muted" datetime="2015-06-11T18:29:20+08:00">Today</time></small>
                                            </div>
                                        </div>
                                    </a><a href="javascript:void(0)">
                                        <div class="media">
                                            <div class="media-left"><span class="avatar avatar-sm avatar-busy rounded-circle"><img src="../../../app-assets/images/portrait/small/avatar-s-2.png" alt="avatar"><i></i></span></div>
                                            <div class="media-body">
                                                <h6 class="media-heading">Bret Lezama</h6>
                                                <p class="notification-text font-small-3 text-muted">I have seen your work, there is</p><small>
                                                    <time class="media-meta text-muted" datetime="2015-06-11T18:29:20+08:00">Tuesday</time></small>
                                            </div>
                                        </div>
                                    </a><a href="javascript:void(0)">
                                        <div class="media">
                                            <div class="media-left">
                                                <div class="avatar avatar-online avatar-sm rounded-circle"><img src="../../../app-assets/images/portrait/small/avatar-s-3.png" alt="avatar"><i></i></div>
                                            </div>
                                            <div class="media-body">
                                                <h6 class="media-heading">Carie Berra</h6>
                                                <p class="notification-text font-small-3 text-muted">Can we have call in this week ?</p><small>
                                                    <time class="media-meta text-muted" datetime="2015-06-11T18:29:20+08:00">Friday</time></small>
                                            </div>
                                        </div>
                                    </a><a href="javascript:void(0)">
                                        <div class="media">
                                            <div class="media-left"><span class="avatar avatar-sm avatar-away rounded-circle"><img src="../../../app-assets/images/portrait/small/avatar-s-6.png" alt="avatar"><i></i></span></div>
                                            <div class="media-body">
                                                <h6 class="media-heading">Eric Alsobrook</h6>
                                                <p class="notification-text font-small-3 text-muted">We have project party this saturday.</p><small>
                                                    <time class="media-meta text-muted" datetime="2015-06-11T18:29:20+08:00">last month</time></small>
                                            </div>
                                        </div>
                                    </a></li>
                                <li class="dropdown-menu-footer"><a class="dropdown-item text-muted text-center" href="javascript:void(0)">Read all messages</a></li>
                            </ul>
                        </li>
                        -->
                        <li id="tablet-font-fix" class="nav-item d-none d-md-block" style="display:flex;align-items:center;margin-right:8px;margin-top:10px;">
                            <div style="position:relative;" id="ts-font-dropdown-wrap-dt">
                                <button onclick="tsFontDropdownToggleDt()" id="ts-font-dropdown-btn-dt" title="Font size" style="display:inline-flex;align-items:center;gap:6px;height:39px;padding:0 10px;border:none;background:#f4f4f5;border-radius:8px;cursor:pointer;color:#374151;font-weight:700;flex-shrink:0;font-size:12px;">
                                    <span style="font-size:13px;line-height:1;">A</span><span id="ts-font-pct-label-dt" style="font-size:10px;font-weight:600;color:#64748b;"></span><i class="fa fa-caret-down" style="font-size:10px;color:#94a3b8;margin-left:1px;"></i>
                                </button>
                                <div id="ts-font-dropdown-menu-dt" style="display:none;position:absolute;top:40px;right:0;background:#fff;border-radius:10px;box-shadow:0 4px 20px rgba(0,0,0,0.15);border:1px solid #e5e7eb;padding:6px;z-index:99999;min-width:140px;">
                                    <div style="display:flex;align-items:center;justify-content:space-between;padding:6px 8px;margin-bottom:4px;">
                                        <span style="font-size:11px;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:0.5px;">Font Size</span>
                                        <span id="ts-font-pct-display-dt" style="font-size:11px;font-weight:700;color:#f97316;"></span>
                                    </div>
                                    <div style="display:flex;align-items:center;gap:6px;padding:4px 8px;">
                                        <button data-ts-font-dir="-1" onclick="tsAdjustFontSize(-1)" style="width:32px;height:32px;border:1.5px solid #e2e8f0;background:#f8fafc;border-radius:8px;cursor:pointer;color:#374151;font-weight:700;font-size:16px;display:inline-flex;align-items:center;justify-content:center;transition:all 0.15s;" onmouseover="this.style.borderColor='#f97316';this.style.color='#f97316'" onmouseout="this.style.borderColor='#e2e8f0';this.style.color='#374151'">−</button>
                                        <div style="flex:1;height:4px;background:#e5e7eb;border-radius:4px;position:relative;overflow:hidden;">
                                            <div id="ts-font-bar-dt" style="height:100%;background:#f97316;border-radius:4px;transition:width 0.2s;"></div>
                                        </div>
                                        <button data-ts-font-dir="1" onclick="tsAdjustFontSize(1)" style="width:32px;height:32px;border:1.5px solid #e2e8f0;background:#f8fafc;border-radius:8px;cursor:pointer;color:#374151;font-weight:700;font-size:16px;display:inline-flex;align-items:center;justify-content:center;transition:all 0.15s;" onmouseover="this.style.borderColor='#f97316';this.style.color='#f97316'" onmouseout="this.style.borderColor='#e2e8f0';this.style.color='#374151'">+</button>
                                    </div>
                                    <button onclick="tsResetFontSize()" style="width:100%;margin-top:4px;height:28px;border:none;background:#f8fafc;border-radius:6px;cursor:pointer;color:#64748b;font-size:11px;font-weight:600;transition:all 0.15s;" onmouseover="this.style.background='#fff7ed';this.style.color='#f97316'" onmouseout="this.style.background='#f8fafc';this.style.color='#64748b'">Reset to 100%</button>
                                </div>
                            </div>
                        </li>
                        <li id="tablet-sale-order-fix" class="nav-item d-none d-md-block" style="display:flex;align-items:center;margin-top:10px;"><a style="display:flex;align-items:center;margin-right:14px;padding:0;" class="nav-link" href="/data_entry/sales_entry/view"><button type="button" class="btn btn-primary m-0"><i class="fa fa-plus" style="color:#fff !important;"></i> <span class="ts-sale-text">Sale</span><span class="ts-order-text"> Order</span></button></a></li>
                        {{-- Hidden logout form used by sidebar logout link --}}
                        <form id="logout-form" action="{{ route('logout') }}" method="POST" class="d-none">
                            @csrf
                        </form>
                    </ul>
                </div>
            </div>
        </div>
    </nav>
<!-- END: Header-->
@hasSection('sidebar')
	@yield('sidebar')
@endif
    <?php
        $Heading='';
        if(Request::segment(1)=='permissionModule'){
            $Heading  = 'Modules';
        }elseif(Request::segment(1)=='permissionGroup'){
            $Heading  = 'Groups';
        }elseif(Request::segment(1)=='permissionurl'){
            $Heading  = 'Group Permission';
        }elseif(Request::segment(1)=='role'){
            $Heading  = 'Roles';
        }elseif(Request::segment(1)=='permissionRole'){
            $Heading  = 'Role Permission';
        }elseif(Request::segment(1)=='user'){
            $Heading  = 'Users';
        }elseif(Request::segment(1)=='customer'){
            $Heading  = 'Customers';
        }elseif(Request::segment(1)=='supplier'){
            $Heading  = 'Suppliers';
        }elseif(Request::segment(1)=='product'){
            $Heading  = 'Products';
        }elseif(Request::segment(1)=='dailyReport'){
            $Heading  = 'Reports';
        }
    ?>
	<!-- BEGIN: Content-->
    <div class="app-content content">
        <div class="content-overlay"></div>
        <div class="content-wrapper">
            <div class="content-header row mb-sm-0">
				
				@include('layouts.breadcrumbs')
			
                <!--<div class="content-header-left col-md-6 col-6 mb-1">
                    <h3 class="content-header-title">{{$Heading}}</h3>
                </div>-->

                <!--<div class="content-header-right breadcrumbs-right breadcrumbs-top col-md-6 col-6">
                    <div class="breadcrumb-wrapper col-12">
                        <ol class="breadcrumb">
                            <?php //if($Heading!=''){ ?>
                                <li class="breadcrumb-item"><a href="/dashboard.view.index">Home</a>
                                </li>
                                <li class="breadcrumb-item"><a href="#">{{$Heading}}</a>
                                </li>
                            <?php //} ?>
                        </ol>
                    </div>
                </div>
                -->

            </div>
            <div class="content-body">
                @include('helpers.flash-message')
                @hasSection('content')
                    @yield('content')
                @endif
            </div>
        </div>
    </div>
    <!-- END: Content-->

    <div class="sidenav-overlay"></div>
    <div class="drag-target"></div>
    <!-- BEGIN: Footer-->
    <footer class="footer footer-static footer-dark navbar-border">
        <p class="clearfix blue-grey lighten-2 text-sm-center mb-0 px-2"><span class="float-md-left d-block d-md-inline-block">Copyright &copy; 2020 </span><span class="float-md-right d-none d-lg-block">Hand-crafted & Made with <i class="feather icon-heart pink"></i></span></p>
    </footer>
    <!-- END: Footer-->
    <!-- BEGIN: Vendor JS-->
    <script src="{{asset('/app-assets/vendors/js/vendors.min.js')}}"></script>
    <script type="text/javascript" src="https://cdnjs.cloudflare.com/ajax/libs/mdbootstrap/4.19.1/js/mdb.min.js"></script>
    <!-- BEGIN Vendor JS-->
    <!-- END: Page Vendor JS-->
    <!-- BEGIN: Theme JS-->


    <script src="{{asset('/app-assets/js/core/app-menu.js')}}?v={{time()}}"></script>
    <script src="{{asset('/app-assets/js/core/app.js')}}"></script>
    <script src="{{asset('/assets/forms.js')}}"></script>
    <script src="{{asset('/app-assets/vendors/js/tables/datatable/datatables.min.js')}}"></script>
    <!-- <script src="{{asset('/app-assets/js/scripts/tables/datatables/datatable-advanced.js')}}"></script> -->
    <script src="{{asset('/assets/js/pdfmake.min.js')}}"></script>
    <script src="{{asset('/assets/js/vfs_fonts.js')}}"></script>
    <script src="{{asset('/assets/js/moment.min.js')}}"></script>
    <script src="{{asset('/assets/js/dataTables.dateTime.min.js')}}"></script>
    <script src="{{asset('/app-assets/vendors/js/forms/icheck/icheck.min.js')}}"></script>
    <script src="{{asset('/app-assets/js/scripts/forms/checkbox-radio.js')}}"></script>
    <script src="{{asset('/app-assets/js/scripts/pages/page-users.js')}}"></script>
    <script src="{{asset('/assets/js/scripts.js')}}"></script>
    <script src="{{asset('/app-assets/js/scripts/modal/components-modal.js')}}"></script>
    <script src="{{asset('/app-assets/vendors/js/extensions/unslider-min.js')}}"></script>
    <script src="{{asset('/app-assets/vendors/js/forms/toggle/bootstrap-checkbox.min.js')}}"></script>
    <script src="{{asset('/app-assets/vendors/js/forms/toggle/switchery.min.js')}}"></script>
    <script src="{{asset('/app-assets/js/scripts/forms/switch.js')}}"></script>
    <script>
    // Force all switchery toggles to use orange instead of red/green
    document.addEventListener('DOMContentLoaded', function() {
        setTimeout(function() {
            document.querySelectorAll('.switchery').forEach(function(el) {
                el.style.cssText += 'background-color: #F27420 !important; border-color: #F27420 !important; box-shadow: inset 0 0 0 16px #F27420 !important;';
                var obs = new MutationObserver(function() {
                    el.style.cssText += 'background-color: #F27420 !important; border-color: #F27420 !important; box-shadow: inset 0 0 0 16px #F27420 !important;';
                });
                obs.observe(el, { attributes: true, attributeFilter: ['style'] });
            });
        }, 300);
    });
    </script>
    <!-- Bootstrap tooltips -->
    <script type="text/javascript" src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.14.4/umd/popper.min.js"></script>
    <!-- MDB core JavaScript -->
    <script src ="https://cdnjs.cloudflare.com/ajax/libs/bootbox.js/4.4.0/bootbox.min.js"></script>
    <!-- END: Theme JS-->
    <!-- END: Page JS-->
	<script type="text/javascript">
	// Close sidebar function for mobile/tablet
	function closeMobileMenu() {
		$('body').removeClass('menu-open menu-expanded');
		$('.main-menu').removeClass('expanded');
		$('.sidenav-overlay').removeClass('d-block').addClass('d-none');
		$('body').css('overflow', '');
	}
	// Close on overlay click
	$(document).on('click touchstart', '.sidenav-overlay', function(e) {
		e.preventDefault();
		closeMobileMenu();
	});
	// Close when touching outside sidebar on mobile/tablet
	$(document).on('touchstart click', function(e) {
		if ($(window).width() <= 1024 && $('body').hasClass('menu-open')) {
			if (!$(e.target).closest('.main-menu').length && !$(e.target).closest('.menu-toggle').length && !$(e.target).closest('.navbar-header').length) {
				closeMobileMenu();
			}
		}
	});
	// Close when a menu item is clicked on mobile
	$(document).on('click', '.main-menu .navigation > li > a', function() {
		if ($(window).width() <= 1024) {
			setTimeout(closeMobileMenu, 150);
		}
	});

	function toggleMenuNav() {
		var newState = $('body').hasClass('menu-collapsed') ? 'menu-expanded' : 'menu-collapsed';
		document.cookie = "menu_state=" + newState + "; path=/; max-age=31536000";
	}

	// Collapsed sidebar fly-out menu (tablet + mobile, max 1024px)
	$(document).ready(function() {
		// Inject fly-out container
		var $flyout = $('<div id="csFlyout"></div>').appendTo('body');

		function closeFlyout() {
			$flyout.hide().empty();
			$('.main-menu .navigation > li').removeClass('cs-flyout-open');
		}

		// Close on outside tap/click
		$(document).on('click touchstart', function(e) {
			if (!$(e.target).closest('#csFlyout, .main-menu .navigation > li > a').length) {
				closeFlyout();
			}
		});

		// Has-sub items: show fly-out
		$(document).on('click', 'body.menu-collapsed .main-menu .navigation > li.has-sub > a', function(e) {
			if (window.innerWidth > 1024) return;
			e.preventDefault();
			e.stopPropagation();
			var $li = $(this).closest('li');

			// Toggle off if already open
			if ($li.hasClass('cs-flyout-open')) { closeFlyout(); return; }
			closeFlyout();
			$li.addClass('cs-flyout-open');

			// Build items from submenu
			var label = $(this).attr('data-label') || $(this).find('.menu-title').text().trim();
			var html = '<div class="csf-title">' + label + '</div>';
			$li.find('> ul.menu-content > li > a').each(function() {
				var href = $(this).attr('href') || '#';
				var txt  = $(this).text().trim();
				var ico  = $(this).find('i').attr('class') || '';
				html += '<a href="' + href + '" class="csf-item"><i class="' + ico + '"></i><span>' + txt + '</span></a>';
			});

			// Position relative to clicked li
			var rect = $li[0].getBoundingClientRect();
			$flyout.html(html).css({ top: rect.top, left: 75 }).show();
		});

		// Don't close when clicking inside fly-out
		$flyout.on('click touchstart', function(e) { e.stopPropagation(); });
	});

	// Desktop sidebar scroll is handled in app-menu.js (document capture wheel handler)
	</script>
    @stack('scripts')
    <script type="text/javascript" src="{{env('CDN_DOMAIN')}}/js/manifest.js?v={{time()}}"></script>
    <script type="text/javascript" src="{{env('CDN_DOMAIN')}}/js/vendor.js?v={{time()}}"></script>
    <script type="text/javascript" src="{{env('CDN_DOMAIN')}}/js/app.js?v={{time()}}"></script>
    <!-- <script src="{{asset('/assets/js/jquery.dataTables.min.js')}}"></script> -->
    <script type="text/javascript">$('#productsTable').DataTable({ordering: false,language : {
            "zeroRecords": " "
        },bInfo : false, bPaginate:false, searching:false });</script>
        
        <script src="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.6-rc.0/js/select2.min.js"></script>
        <script>
            $('.select2').select2();
        </script>

	<script>
	  $(document).ready(function () {
		// Initialize all current tooltips
		$('[data-bs-toggle="tooltip"]').tooltip();

		// Re-initialize tooltips for dynamically added elements (like DataTables)
		$(document).on('mouseenter', '[data-bs-toggle="tooltip"]', function () {
			if (!$(this).data('bs.tooltip')) { // avoid re-initializing
				new bootstrap.Tooltip(this);
			}
		});
	});
	</script>

    @include('helpers.color-picker')

    <style>
    /* Tablet landscape keyboard fixes */
    .keyboard-open .react-select__menu,
    .keyboard-open .react-select__menu-list {
        max-height: 120px !important;
    }
    .keyboard-open .dropdown-menu {
        max-height: 150px !important;
        overflow-y: auto !important;
    }
    .keyboard-open .select2-results__options {
        max-height: 120px !important;
    }
    </style>
    <script>
    (function(){
        // Detect keyboard open/close via visualViewport
        if (!window.visualViewport) return;
        var vv = window.visualViewport;
        var lastHeight = vv.height;

        vv.addEventListener('resize', function() {
            var diff = lastHeight - vv.height;
            // Keyboard opened (height reduced by >150px)
            if (vv.height < lastHeight * 0.75) {
                document.body.classList.add('keyboard-open');
                // Scroll focused element into view
                var el = document.activeElement;
                if (el && (el.tagName === 'INPUT' || el.tagName === 'TEXTAREA')) {
                    setTimeout(function() {
                        el.scrollIntoView({ behavior: 'smooth', block: 'center' });
                    }, 150);
                }
            } else {
                document.body.classList.remove('keyboard-open');
            }
        });

        // Update base height on orientation change
        window.addEventListener('orientationchange', function() {
            setTimeout(function() { lastHeight = vv.height; }, 500);
        });
    })();
    </script>

    <script>
    (function(){
        var key = 'ts_invoice_view';
        var current = localStorage.getItem(key) || 'off';
        function applyInvoiceView(val) {
            current = val;
            localStorage.setItem(key, val);
            var isOn = val === 'on';
            // Update tablet button
            var btnT = document.getElementById('invoice-view-btn-tablet');
            if (btnT) {
                btnT.style.background = isOn ? 'linear-gradient(135deg,#f97316,#ea580c)' : '#f4f4f5';
                btnT.style.color = isOn ? '#fff' : '#64748b';
            }
            var lbl = document.getElementById('invoice-view-label-tablet');
            if (lbl) lbl.textContent = isOn ? 'Card View' : 'Table View';
            // Update mobile navbar toggle
            var track = document.getElementById('mobile-dv-track');
            var thumb = document.getElementById('mobile-dv-thumb');
            var mlbl = document.getElementById('mobile-dv-label');
            if (track) track.style.background = isOn ? '#f97316' : '#d1d5db';
            if (thumb) thumb.style.left = isOn ? '16px' : '3px';
            if (mlbl) mlbl.style.color = isOn ? '#f97316' : '#64748b';
            // Notify React
            window.dispatchEvent(new CustomEvent('ts-invoice-view', { detail: val }));
        }
        window.tsToggleInvoiceView = function() { applyInvoiceView(current === 'on' ? 'off' : 'on'); };
        window.tsMobileDVToggle = function() { applyInvoiceView(current === 'on' ? 'off' : 'on'); };
        // Apply on load
        document.addEventListener('DOMContentLoaded', function(){ applyInvoiceView(current); });
    })();
    </script>

    <script>
    (function(){
        /* Font size levels — percentages of html base font size (16px = 100%) */
        var LEVELS = [85, 90, 95, 100, 105, 110, 115];
        var DEFAULT = 3; /* index of 100% */
        var key = 'ts_font_size';
        var idx = parseInt(localStorage.getItem(key));
        if (isNaN(idx) || idx < 0 || idx >= LEVELS.length) idx = DEFAULT;

        /* Screen-aware limits — prevent over-scaling on small screens */
        function getLimits() {
            var w = window.innerWidth;
            if (w < 768)  return { min: 1, max: 5 }; /* mobile:  90%–110% */
            if (w < 1200) return { min: 0, max: 6 }; /* tablet:  85%–115% */
            return           { min: 0, max: 6 };      /* desktop: 85%–115% */
        }

        function updateDropdownUI(pct) {
            var lim = getLimits();
            var range = lim.max - lim.min;
            var progress = range > 0 ? ((idx - lim.min) / range) * 100 : 50;
            /* Update both mobile and desktop dropdowns */
            ['', '-dt'].forEach(function(suffix) {
                var lbl = document.getElementById('ts-font-pct-label' + suffix);
                if (lbl) lbl.textContent = pct + '%';
                var disp = document.getElementById('ts-font-pct-display' + suffix);
                if (disp) disp.textContent = pct + '%';
                var bar = document.getElementById('ts-font-bar' + suffix);
                if (bar) bar.style.width = progress + '%';
            });
        }

        function applyFontSize(i) {
            var lim = getLimits();
            i = Math.max(lim.min, Math.min(lim.max, i));
            idx = i;
            var scale = LEVELS[i] / 100;
            /*
             * Use CSS zoom on .content-wrapper — scales text and content
             * proportionally without breaking layout or causing overflow.
             * At 100% zoom is removed completely so UI is pixel-perfect.
             */
            var root = document.querySelector('.content-wrapper');
            if (root) {
                if (scale === 1) {
                    root.style.removeProperty('zoom');
                    root.style.removeProperty('-moz-transform');
                    root.style.removeProperty('-moz-transform-origin');
                } else {
                    root.style.zoom = scale;
                    /* Firefox fallback (no zoom support) */
                    root.style.MozTransform = 'scale(' + scale + ')';
                    root.style.MozTransformOrigin = 'top left';
                }
            }
            localStorage.setItem(key, i);
            updateDropdownUI(LEVELS[i]);
            updateBtnStates();
        }

        /* Dim -/+ buttons when at min/max limit */
        function updateBtnStates() {
            var lim = getLimits();
            document.querySelectorAll('[data-ts-font-dir]').forEach(function(btn) {
                var dir = parseInt(btn.getAttribute('data-ts-font-dir'));
                var atLimit = dir < 0 ? idx <= lim.min : idx >= lim.max;
                btn.style.opacity = atLimit ? '0.35' : '1';
                btn.style.cursor  = atLimit ? 'default' : 'pointer';
            });
        }

        /* Dropdown toggles */
        window.tsFontDropdownToggle = function() {
            var menu = document.getElementById('ts-font-dropdown-menu');
            if (menu) menu.style.display = menu.style.display === 'none' ? 'block' : 'none';
        };
        window.tsFontDropdownToggleDt = function() {
            var menu = document.getElementById('ts-font-dropdown-menu-dt');
            if (menu) menu.style.display = menu.style.display === 'none' ? 'block' : 'none';
        };

        /* Close dropdowns on outside click */
        document.addEventListener('click', function(e) {
            [['ts-font-dropdown-wrap','ts-font-dropdown-menu'],['ts-font-dropdown-wrap-dt','ts-font-dropdown-menu-dt']].forEach(function(pair) {
                var wrap = document.getElementById(pair[0]);
                var menu = document.getElementById(pair[1]);
                if (wrap && menu && !wrap.contains(e.target)) menu.style.display = 'none';
            });
        });

        /* Reset to 100% */
        window.tsResetFontSize = function() { applyFontSize(DEFAULT); };

        applyFontSize(idx);
        window.addEventListener('resize', function() { applyFontSize(idx); });

        window.tsAdjustFontSize = function(dir) { applyFontSize(idx + dir); };
    })();
    </script>

<div id="datepicker-portal" style="position:relative;z-index:99999;"></div>
</body>
<!-- END: Body-->

</html>
