<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>@yield('title', 'Admin Dashboard')</title>

  <!-- SEO Optimization -->
  <meta name="description" content="Bits&Bobbins admin dashboard">
  <meta name="author" content="SoloDesignStudio">

  <!-- Favicon -->
  <link rel="icon" type="image/png" href="{{ asset('assets/dashboard/images/favicon.ico') }}">

  <!-- Local Third-Party Libraries (100% Offline Compatible) -->
  <link rel="stylesheet" href="{{ asset('assets/dashboard/libs/bootstrap/css/bootstrap.min.css') }}">
  <link rel="stylesheet" href="{{ asset('assets/dashboard/libs/bootstrap-icons/bootstrap-icons.css') }}">
  <link rel="stylesheet" href="{{ asset('assets/dashboard/libs/apexcharts/apexcharts.css') }}">
  <link rel="stylesheet" href="{{ asset('assets/dashboard/libs/flatpickr/flatpickr.min.css') }}">

  <!-- Main Design System & Custom Stylesheet -->
  <link rel="stylesheet" href="{{ asset('assets/dashboard/css/main.css') }}">
  @yield('styles')
</head>

<body>

  @include('admin.partials.sidebar')

  <!-- ==========================================
         START: Main Content Area
         ========================================== -->
  <div class="main-wrapper">

    @include('admin.partials.topbar')

    @yield('content')

    @include('admin.partials.admin-footer')

  </div>
  <!-- ==========================================
         END: Main Content Area
         ========================================== -->

  @include('admin.partials.scripts')
  @yield('scripts')
</body>

</html>

