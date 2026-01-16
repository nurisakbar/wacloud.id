<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>

    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta name="description" content="@yield('meta_description', '')">
    <meta name="author" content="@yield('meta_author', '')">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'WAHA SaaS') }} - @yield('title', 'Dashboard')</title>

    <!-- Custom fonts for this template-->
    <link href="{{ asset('startbootstrap/vendor/fontawesome-free/css/all.min.css') }}" rel="stylesheet"
        type="text/css">
    <link
        href="https://fonts.googleapis.com/css?family=Nunito:200,200i,300,300i,400,400i,600,600i,700,700i,800,800i,900,900i"
        rel="stylesheet">

    <!-- Custom styles for this template-->
    <link href="{{ asset('startbootstrap/css/sb-admin-2.min.css') }}" rel="stylesheet">

    @stack('styles')
    
    <style>
        /* Badge Color Contrast Improvements - Ensure text readability */
        /* Badge dengan background gelap - gunakan text putih */
        .badge.bg-primary:not(.text-dark):not(.text-black),
        .badge.bg-success:not(.text-dark):not(.text-black),
        .badge.bg-danger:not(.text-dark):not(.text-black),
        .badge.bg-info:not(.text-dark):not(.text-black),
        .badge.bg-secondary:not(.text-dark):not(.text-black),
        .badge.bg-dark:not(.text-dark):not(.text-black) {
            color: #ffffff !important;
        }
        
        /* Badge dengan background terang (warning) - gunakan text gelap */
        .badge.bg-warning:not(.text-white):not(.text-light) {
            color: #000000 !important;
            font-weight: 600;
        }
        
        /* Badge dengan bg-opacity - pastikan kontras baik */
        .badge.bg-primary.bg-opacity-10 {
            color: #0d6efd !important;
            background-color: rgba(13, 110, 253, 0.1) !important;
        }
        
        .badge.bg-success.bg-opacity-10 {
            color: #198754 !important;
            background-color: rgba(25, 135, 84, 0.1) !important;
        }
        
        .badge.bg-danger.bg-opacity-10 {
            color: #dc3545 !important;
            background-color: rgba(220, 53, 69, 0.1) !important;
        }
        
        .badge.bg-info.bg-opacity-10 {
            color: #0dcaf0 !important;
            background-color: rgba(13, 202, 240, 0.1) !important;
        }
        
        .badge.bg-warning.bg-opacity-10 {
            color: #ffc107 !important;
            background-color: rgba(255, 193, 7, 0.1) !important;
        }
        
        .badge.bg-secondary.bg-opacity-10 {
            color: #6c757d !important;
            background-color: rgba(108, 117, 125, 0.1) !important;
        }
        
        /* Badge dengan border - pastikan kontras baik */
        .badge.border-primary {
            border-color: #0d6efd !important;
        }
        
        .badge.border-success {
            border-color: #198754 !important;
        }
        
        .badge.border-danger {
            border-color: #dc3545 !important;
        }
        
        .badge.border-info {
            border-color: #0dcaf0 !important;
        }
        
        .badge.border-warning {
            border-color: #ffc107 !important;
        }
        
        .badge.border-secondary {
            border-color: #6c757d !important;
        }
        
        /* Legacy badge classes (Bootstrap 4 style) */
        .badge.badge-primary {
            background-color: #4e73df !important;
            color: #ffffff !important;
        }
        
        .badge.badge-success {
            background-color: #1cc88a !important;
            color: #ffffff !important;
        }
        
        .badge.badge-danger {
            background-color: #e74a3b !important;
            color: #ffffff !important;
        }
        
        .badge.badge-info {
            background-color: #36b9cc !important;
            color: #ffffff !important;
        }
        
        .badge.badge-warning {
            background-color: #f6c23e !important;
            color: #000000 !important;
            font-weight: 600;
        }
        
        .badge.badge-secondary {
            background-color: #858796 !important;
            color: #ffffff !important;
        }
        
        .support-whatsapp-btn {
            position: fixed;
            bottom: 30px;
            right: 30px;
            width: 60px;
            height: 60px;
            background-color: #25D366;
            color: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 28px;
            box-shadow: 0 4px 12px rgba(37, 211, 102, 0.4);
            z-index: 1000;
            transition: all 0.3s ease;
            text-decoration: none;
        }
        
        .support-whatsapp-btn:hover {
            background-color: #20BA5A;
            transform: scale(1.1);
            box-shadow: 0 6px 16px rgba(37, 211, 102, 0.6);
            color: white;
            text-decoration: none;
        }
        
        .support-whatsapp-btn:active {
            transform: scale(0.95);
        }
        
        @media (max-width: 768px) {
            .support-whatsapp-btn {
                width: 56px;
                height: 56px;
                font-size: 24px;
                bottom: 20px;
                right: 20px;
            }
        }
    </style>
</head>

<body id="page-top">

    <!-- Page Wrapper -->
    <div id="wrapper">

        <!-- Sidebar -->
        <ul class="navbar-nav bg-gradient-primary sidebar sidebar-dark accordion" id="accordionSidebar">

            <!-- Sidebar - Brand -->
            <a class="sidebar-brand d-flex align-items-center justify-content-center" href="{{ route('home') }}">
                <div class="sidebar-brand-icon rotate-n-15">
                    <i class="fas fa-comments"></i>
                </div>
                <div class="sidebar-brand-text mx-3">
                    {{ config('app.name', 'WAHA SaaS') }}
                </div>
            </a>

            <!-- Divider -->
            <hr class="sidebar-divider my-0">

            @auth
            @if(auth()->check() && in_array(auth()->user()->role, ['admin', 'super_admin']))
            {{-- ADMIN MENU --}}
            <!-- Nav Item - Admin Dashboard -->
            <li class="nav-item {{ request()->routeIs('admin.dashboard.*') || request()->routeIs('home') ? 'active' : '' }}">
                <a class="nav-link" href="{{ route('admin.dashboard.index') }}">
                    <i class="fas fa-fw fa-tachometer-alt"></i>
                    <span>{{ __('Admin Dashboard') }}</span>
                </a>
            </li>

            <!-- Divider -->
            <hr class="sidebar-divider">

            <!-- Heading -->
            <div class="sidebar-heading">
                {{ __('Management') }}
            </div>

            <!-- Nav Item - Users -->
            <li class="nav-item {{ request()->routeIs('admin.users.*') ? 'active' : '' }}">
                <a class="nav-link" href="{{ route('admin.users.index') }}">
                    <i class="fas fa-fw fa-users"></i>
                    <span>{{ __('Users') }}</span>
                </a>
            </li>

            <!-- Nav Item - Payment Reports -->
            <li class="nav-item {{ request()->routeIs('admin.quota-purchases.*') ? 'active' : '' }}">
                <a class="nav-link" href="{{ route('admin.quota-purchases.index') }}">
                    <i class="fas fa-fw fa-file-invoice-dollar"></i>
                    <span>{{ __('Payment Reports') }}</span>
                </a>
            </li>

            <!-- Nav Item - Pricing Settings -->
            <li class="nav-item {{ request()->routeIs('admin.pricing.*') ? 'active' : '' }}">
                <a class="nav-link" href="{{ route('admin.pricing.index') }}">
                    <i class="fas fa-fw fa-dollar-sign"></i>
                    <span>{{ __('Pricing Settings') }}</span>
                </a>
            </li>

            <!-- Nav Item - Referral Settings -->
            <li class="nav-item {{ request()->routeIs('admin.referral-settings.*') ? 'active' : '' }}">
                <a class="nav-link" href="{{ route('admin.referral-settings.index') }}">
                    <i class="fas fa-fw fa-user-friends"></i>
                    <span>{{ __('Referral Settings') }}</span>
                </a>
            </li>

            <!-- Nav Item - Settings -->
            <li class="nav-item {{ request()->routeIs('admin.settings.*') ? 'active' : '' }}">
                <a class="nav-link" href="{{ route('admin.settings.index') }}">
                    <i class="fas fa-fw fa-cog"></i>
                    <span>{{ __('Settings') }}</span>
                </a>
            </li>

            <!-- Divider -->
            <hr class="sidebar-divider">

            <!-- Heading -->
            <div class="sidebar-heading">
                {{ __('Account') }}
            </div>

            <!-- Nav Item - Profile -->
            <li class="nav-item {{ request()->routeIs('profile.*') ? 'active' : '' }}">
                <a class="nav-link" href="{{ route('profile.show') }}">
                    <i class="fas fa-fw fa-user"></i>
                    <span>{{ __('Profile') }}</span>
                </a>
            </li>

            @else
            {{-- CLIENT MENU --}}
            <!-- Nav Item - Dashboard -->
            <li class="nav-item {{ request()->routeIs('home') ? 'active' : '' }}">
                <a class="nav-link" href="{{ route('home') }}">
                    <i class="fas fa-fw fa-tachometer-alt"></i>
                    <span>{{ __('Dashboard') }}</span>
                </a>
            </li>

            <!-- Divider -->
            <hr class="sidebar-divider">

            <!-- Heading -->
            <div class="sidebar-heading">
                {{ __('WhatsApp') }}
            </div>

            <!-- Nav Item - Devices -->
            <li class="nav-item {{ request()->routeIs('sessions.*') ? 'active' : '' }}">
                <a class="nav-link" href="{{ route('sessions.index') }}">
                    <i class="fas fa-fw fa-mobile-alt"></i>
                    <span>{{ __('Kelola Device') }}</span>
                </a>
            </li>

            <!-- Nav Item - Quota -->
            <li class="nav-item {{ request()->routeIs('quota.*') ? 'active' : '' }}">
                <a class="nav-link" href="{{ route('quota.index') }}">
                    <i class="fas fa-fw fa-wallet"></i>
                    <span>{{ __('Pembelian Quota') }}</span>
                </a>
            </li>

            <!-- Nav Item - Messages -->
            <li class="nav-item {{ request()->routeIs('messages.*') ? 'active' : '' }}">
                <a class="nav-link" href="{{ route('messages.index') }}">
                    <i class="fas fa-fw fa-envelope"></i>
                    <span>{{ __('Kelola Pesan') }}</span>
                </a>
            </li>

            <!-- Nav Item - Templates -->
            <li class="nav-item {{ request()->routeIs('templates.*') ? 'active' : '' }}">
                <a class="nav-link" href="{{ route('templates.index') }}">
                    <i class="fas fa-fw fa-file-alt"></i>
                    <span>{{ __('Kelola Template') }}</span>
                </a>
            </li>

            <!-- Divider -->
            <hr class="sidebar-divider">

            <!-- Heading -->
            <div class="sidebar-heading">
                {{ __('Integration') }}
            </div>

            <!-- Nav Item - Webhooks -->
            <li class="nav-item {{ request()->routeIs('webhooks.*') ? 'active' : '' }}">
                <a class="nav-link" href="{{ route('webhooks.index') }}">
                    <i class="fas fa-fw fa-code-branch"></i>
                    <span>{{ __('Webhooks') }}</span>
                </a>
            </li>

            <!-- Nav Item - API Keys -->
            <li class="nav-item {{ request()->routeIs('api-keys.*') ? 'active' : '' }}">
                <a class="nav-link" href="{{ route('api-keys.index') }}">
                    <i class="fas fa-fw fa-key"></i>
                    <span>{{ __('API Keys') }}</span>
                </a>
            </li>

            <!-- Nav Item - API Documentation -->
            <li class="nav-item {{ request()->routeIs('api-docs.*') ? 'active' : '' }}">
                <a class="nav-link" href="{{ route('api-docs.index') }}">
                    <i class="fas fa-fw fa-book"></i>
                    <span>{{ __('API Docs') }}</span>
                </a>
            </li>

            <!-- Divider -->
            <hr class="sidebar-divider">

            <!-- Heading -->
            <div class="sidebar-heading">
                {{ __('Account') }}
            </div>

            <!-- Nav Item - Profile -->
            <li class="nav-item {{ request()->routeIs('profile.*') ? 'active' : '' }}">
                <a class="nav-link" href="{{ route('profile.show') }}">
                    <i class="fas fa-fw fa-user"></i>
                    <span>{{ __('Profile') }}</span>
                </a>
            </li>

            <!-- Nav Item - Referral -->
            <li class="nav-item {{ request()->routeIs('referral.*') ? 'active' : '' }}">
                <a class="nav-link" href="{{ route('referral.index') }}">
                    <i class="fas fa-fw fa-user-friends"></i>
                    <span>{{ __('Referral') }}</span>
                </a>
            </li>
            @endif
            @endauth

            <!-- Divider -->
            <hr class="sidebar-divider d-none d-md-block">

            <!-- Sidebar Toggler (Sidebar) -->
            <div class="text-center d-none d-md-inline">
                <button class="rounded-circle border-0" id="sidebarToggle"></button>
            </div>



        </ul>
        <!-- End of Sidebar -->

        <!-- Content Wrapper -->
        <div id="content-wrapper" class="d-flex flex-column">

            <!-- Main Content -->
            <div id="content">

                <!-- Topbar -->
                <nav class="navbar navbar-expand navbar-light bg-white topbar mb-4 static-top shadow">

                    <!-- Sidebar Toggle (Topbar) -->
                    <button id="sidebarToggleTop" class="btn btn-link d-md-none rounded-circle mr-3">
                        <i class="fa fa-bars"></i>
                    </button>

                    <!-- Topbar Search -->
                    <form
                        class="d-none d-sm-inline-block form-inline mr-auto ml-md-3 my-2 my-md-0 mw-100 navbar-search">
                        <div class="input-group">
                            <input type="text" class="form-control bg-light border-0 small" placeholder="Search for..."
                                aria-label="Search" aria-describedby="basic-addon2">
                            <div class="input-group-append">
                                <button class="btn btn-primary" type="button">
                                    <i class="fas fa-search fa-sm"></i>
                                </button>
                            </div>
                        </div>
                    </form>

                    <!-- Topbar Navbar -->
                    <ul class="navbar-nav ml-auto">

                        <!-- Nav Item - Search Dropdown (Visible Only XS) -->
                        <li class="nav-item dropdown no-arrow d-sm-none">
                            <a class="nav-link dropdown-toggle" href="#" id="searchDropdown" role="button"
                                data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                <i class="fas fa-search fa-fw"></i>
                            </a>
                            <!-- Dropdown - Messages -->
                            <div class="dropdown-menu dropdown-menu-right p-3 shadow animated--grow-in"
                                aria-labelledby="searchDropdown">
                                <form class="form-inline mr-auto w-100 navbar-search">
                                    <div class="input-group">
                                        <input type="text" class="form-control bg-light border-0 small"
                                            placeholder="Search for..." aria-label="Search"
                                            aria-describedby="basic-addon2">
                                        <div class="input-group-append">
                                            <button class="btn btn-primary" type="button">
                                                <i class="fas fa-search fa-sm"></i>
                                            </button>
                                        </div>
                                    </div>
                                </form>
                            </div>
                        </li>

                        <div class="topbar-divider d-none d-sm-block"></div>

                        <!-- Nav Item - User Information -->
                        <li class="nav-item dropdown no-arrow">
                            <a class="nav-link dropdown-toggle" href="#" id="userDropdown" role="button"
                                data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                <span class="mr-2 d-none d-lg-inline text-gray-600 small">
                                    @auth
                                        {{ Auth::user()->name }}
                                    @else
                                        Guest
                                    @endauth
                                </span>
                                <img class="img-profile rounded-circle"
                                    src="{{ asset('startbootstrap/img/undraw_profile.svg') }}">
                            </a>
                            <!-- Dropdown - User Information -->
                            <div class="dropdown-menu dropdown-menu-right shadow animated--grow-in"
                                aria-labelledby="userDropdown">
                                @auth
                                <a class="dropdown-item" href="{{ route('profile.show') }}">
                                    <i class="fas fa-user fa-sm fa-fw mr-2 text-gray-400"></i>
                                    {{ __('Profile') }}
                                </a>
                                <div class="dropdown-divider"></div>
                                @endauth
                                @auth
                                    <a class="dropdown-item" href="#" data-toggle="modal" data-target="#logoutModal">
                                        <i class="fas fa-sign-out-alt fa-sm fa-fw mr-2 text-gray-400"></i>
                                        Logout
                                    </a>
                                @else
                                    <a class="dropdown-item" href="{{ route('login') }}">
                                        <i class="fas fa-sign-in-alt fa-sm fa-fw mr-2 text-gray-400"></i>
                                        Login
                                    </a>
                                @endauth
                            </div>
                        </li>

                    </ul>

                </nav>
                <!-- End of Topbar -->

                <!-- Begin Page Content -->
                <div class="container-fluid">
                    @if (session('success'))
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            <i class="fas fa-check-circle"></i> {{ session('success') }}
                            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                    @endif

                    @if (session('error') || (isset($errors) && $errors->any()))
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <i class="fas fa-exclamation-triangle"></i>
                            @if(session('error'))
                                {{ session('error') }}
                            @else
                                <ul class="mb-0">
                                    @foreach ($errors->all() as $error)
                                        <li>{{ $error }}</li>
                                    @endforeach
                                </ul>
                            @endif
                            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                    @endif

                    @yield('content')
                </div>
                <!-- /.container-fluid -->

            </div>
            <!-- End of Main Content -->

            <!-- Footer -->
            <footer class="sticky-footer bg-white">
                <div class="container my-auto">
                    <div class="copyright text-center my-auto">
                        <span>Copyright &copy; {{ config('app.name', 'WAHA SaaS') }}
                            {{ date('Y') }}</span>
                    </div>
                </div>
            </footer>
            <!-- End of Footer -->

        </div>
        <!-- End of Content Wrapper -->

    </div>
    <!-- End of Page Wrapper -->

    <!-- Scroll to Top Button-->
    <a class="scroll-to-top rounded" href="#page-top">
        <i class="fas fa-angle-up"></i>
    </a>

    <!-- Support WhatsApp Floating Button -->
    <a href="https://wa.me/6289699935552?text=Hallo%20mimin%20wacloud.id,%20saya%20ingin%20diskusi" 
       target="_blank" 
       class="support-whatsapp-btn"
       title="Hubungi Support">
        <i class="fab fa-whatsapp"></i>
    </a>

    <!-- Logout Modal-->
    <div class="modal fade" id="logoutModal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel"
        aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="exampleModalLabel">Ready to Leave?</h5>
                    <button class="close" type="button" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">×</span>
                    </button>
                </div>
                <div class="modal-body">Select "Logout" below if you are ready to end your current session.</div>
                <div class="modal-footer">
                    <button class="btn btn-secondary" type="button" data-dismiss="modal">Cancel</button>
                    @auth
                        <form id="logout-form" action="{{ route('logout') }}" method="POST" class="d-inline">
                            @csrf
                            <button type="submit" class="btn btn-primary">Logout</button>
                        </form>
                    @else
                        <a class="btn btn-primary" href="{{ route('login') }}">Login</a>
                    @endauth
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap core JavaScript-->
    <script src="{{ asset('startbootstrap/vendor/jquery/jquery.min.js') }}"></script>
    <script src="{{ asset('startbootstrap/vendor/bootstrap/js/bootstrap.bundle.min.js') }}"></script>

    <!-- Core plugin JavaScript-->
    <script src="{{ asset('startbootstrap/vendor/jquery-easing/jquery.easing.min.js') }}"></script>

    <!-- Custom scripts for all pages-->
    <script src="{{ asset('startbootstrap/js/sb-admin-2.min.js') }}"></script>

    <!-- Page level plugins -->
    <script src="{{ asset('startbootstrap/vendor/chart.js/Chart.min.js') }}"></script>

    @stack('scripts')

</body>

</html>


