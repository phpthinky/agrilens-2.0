<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>@hasSection('title')@yield('title') — @endif{{ config('app.name', 'AgriLens 2.0') }}</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700,800" rel="stylesheet">

    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" rel="stylesheet">

    <style>
        :root {
            --agri-900: #0f3d1a;
            --agri-800: #17501f;
            --agri-700: #1f6b2c;
            --agri-600: #2e7d32;
            --agri-500: #388e3c;
            --agri-400: #4caf50;
            --agri-100: #e8f5e9;
            --agri-soil: #8d6e4b;
            --agri-amber: #f9a825;
            --agri-bg: #f3f6f2;
            --agri-ink: #1c2b1e;
            --agri-radius: .625rem;
        }

        * { -webkit-font-smoothing: antialiased; }

        body {
            background-color: var(--agri-bg);
            background-image:
                radial-gradient(circle at 1px 1px, rgba(46,125,50,.05) 1px, transparent 0);
            background-size: 22px 22px;
            font-family: 'Inter', 'Segoe UI', system-ui, sans-serif;
            color: var(--agri-ink);
        }

        h1, h2, h3, h4, h5, h6 { font-weight: 700; letter-spacing: -.01em; }

        /* ── Top navbar ─────────────────────────────────────────── */
        .navbar {
            background: linear-gradient(100deg, var(--agri-900), var(--agri-600) 65%, var(--agri-500)) !important;
            box-shadow: 0 2px 10px rgba(15,61,26,.35);
            padding-top: .55rem;
            padding-bottom: .55rem;
        }
        .navbar .nav-link          { color: rgba(255,255,255,.82) !important; font-weight: 500; }
        .navbar .nav-link:hover,
        .navbar .nav-link.active   { color: #fff !important; }
        .navbar .dropdown-item:hover { background-color: var(--agri-100); }
        .navbar .nav-link.active {
            border-bottom: 2px solid var(--agri-amber);
        }
        .navbar-brand {
            color: #fff !important;
            font-weight: 800;
            letter-spacing: -.02em;
            display: flex;
            align-items: center;
            gap: .6rem;
        }
        .brand-mark {
            width: 36px; height: 36px; border-radius: 10px;
            background: rgba(255,255,255,.16);
            border: 1px solid rgba(255,255,255,.25);
            display: flex; align-items: center; justify-content: center;
            color: #d4f5d8; font-size: 1.05rem;
            flex-shrink: 0;
        }
        .brand-version {
            font-weight: 500;
            font-size: .65rem;
            color: rgba(255,255,255,.65);
            letter-spacing: .04em;
            display: block;
            margin-top: -2px;
        }
        .navbar-toggler { border-color: rgba(255,255,255,.4); }
        .navbar-toggler-icon {
            background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 30 30'%3e%3cpath stroke='rgba(255,255,255,.75)' stroke-linecap='round' stroke-miterlimit='10' stroke-width='2' d='M4 7h22M4 15h22M4 23h22'/%3e%3c/svg%3e");
        }

        /* ── Mobile collapsed navbar ─────────────────────────────── */
        @media (max-width: 767.98px) {
            .navbar-collapse {
                background: var(--agri-800);
                border-radius: 0 0 10px 10px;
                padding: 6px 4px 10px;
                margin: 4px -12px -8px;
            }
            .navbar .dropdown-menu {
                background: var(--agri-600);
                border: none;
                margin-left: 1rem;
            }
            .navbar .dropdown-menu .dropdown-item {
                color: rgba(255,255,255,.85) !important;
                font-size: .9rem;
            }
            .navbar .dropdown-menu .dropdown-item:hover,
            .navbar .dropdown-menu .dropdown-item:focus {
                background: rgba(255,255,255,.15);
                color: #fff !important;
            }
            .navbar .dropdown-divider { border-color: rgba(255,255,255,.15); }
            .mobile-nav-section {
                font-size: .7rem;
                font-weight: 700;
                letter-spacing: 1px;
                text-transform: uppercase;
                color: rgba(255,255,255,.4);
                padding: .6rem 1rem .1rem;
            }
        }

        /* ── Sidebar ─────────────────────────────────────────────── */
        .sidebar {
            min-height: calc(100vh - 62px);
            background: linear-gradient(190deg, var(--agri-800), var(--agri-900));
            padding-top: 0;
            width: 232px;
            flex-shrink: 0;
        }
        .sidebar-user {
            display: flex;
            align-items: center;
            gap: .65rem;
            padding: 1.1rem 1.2rem .9rem;
            border-bottom: 1px solid rgba(255,255,255,.08);
            margin-bottom: .4rem;
        }
        .sidebar-user-avatar {
            width: 38px; height: 38px; border-radius: 50%;
            background: rgba(255,255,255,.15);
            border: 1px solid rgba(255,255,255,.2);
            display: flex; align-items: center; justify-content: center;
            font-weight: 700; color: #fff; font-size: .95rem;
            flex-shrink: 0;
        }
        .sidebar-user-name {
            color: #fff; font-weight: 600; font-size: .875rem;
            line-height: 1.2; margin: 0;
            white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
        }
        .sidebar-user-role {
            font-size: .68rem; font-weight: 600; letter-spacing: .04em;
            text-transform: uppercase; color: var(--agri-amber);
        }
        .sidebar .nav-link {
            color: rgba(255,255,255,.72);
            padding: .55rem 1.1rem;
            margin: 1px 8px;
            border-radius: 6px;
            border-left: 3px solid transparent;
            transition: background .15s, color .15s;
            font-size: .875rem;
            font-weight: 500;
        }
        .sidebar .nav-link:hover {
            background: rgba(255,255,255,.08);
            color: #fff;
        }
        .sidebar .nav-link.active {
            background: rgba(255,255,255,.12);
            color: #fff;
            border-left-color: var(--agri-amber);
        }
        .sidebar .nav-link i { width: 18px; text-align: center; margin-right: 8px; }
        .sidebar-section {
            font-size: .68rem;
            font-weight: 700;
            letter-spacing: .08em;
            text-transform: uppercase;
            color: rgba(255,255,255,.35);
            padding: .8rem 1.3rem .25rem;
        }
        .sidebar-divider {
            border-top: 1px solid rgba(255,255,255,.08);
            margin: .4rem 1rem;
        }

        /* ── Main content ────────────────────────────────────────── */
        .app-body { display: flex; }
        .main-content {
            flex: 1;
            padding: 1.75rem 2.25rem;
            min-width: 0;
        }

        /* ── Cards ───────────────────────────────────────────────── */
        .card {
            border: none;
            box-shadow: 0 1px 3px rgba(15,61,26,.06), 0 6px 18px rgba(15,61,26,.06);
            border-radius: var(--agri-radius);
        }
        .card-header {
            border-radius: var(--agri-radius) var(--agri-radius) 0 0 !important;
            font-weight: 600;
            background-color: rgba(46,125,50,.04);
            border-bottom: 1px solid rgba(46,125,50,.08);
        }

        /* ── Buttons ─────────────────────────────────────────────── */
        .btn { border-radius: 7px; font-weight: 500; }
        .btn-success { background-color: var(--agri-500); border-color: var(--agri-600); }
        .btn-success:hover { background-color: var(--agri-600); border-color: var(--agri-700); }
        .btn-outline-success { color: var(--agri-600); border-color: var(--agri-500); }
        .btn-outline-success:hover { background-color: var(--agri-500); border-color: var(--agri-500); }

        /* ── Tables ──────────────────────────────────────────────── */
        .table thead th {
            font-weight: 600; font-size: .8rem;
            text-transform: uppercase; letter-spacing: .03em;
            color: #4b5f4d;
        }
        .table-success { --bs-table-bg: var(--agri-100); }

        /* ── Alerts ──────────────────────────────────────────────── */
        .alert { border-radius: 8px; border: none; }
        .alert-success { background-color: var(--agri-100); color: var(--agri-800); }

        /* ── Badges ──────────────────────────────────────────────── */
        .badge { font-weight: 600; letter-spacing: .01em; }

        @yield('styles')
    </style>
</head>
<body>

    {{-- ── Top Navbar ─────────────────────────────────────────── --}}
    <nav class="navbar navbar-expand-md">
        <div class="container-fluid px-3">
            <a class="navbar-brand" href="{{ url('/') }}">
                <span class="brand-mark"><i class="fa fa-seedling"></i></span>
                <span>
                    {{ config('app.name', 'AgriLens') }}
                    <span class="brand-version">Soil Fertility &amp; Fertilizer Advisory System</span>
                </span>
            </a>

            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarContent">
                <span class="navbar-toggler-icon"></span>
            </button>

            <div class="collapse navbar-collapse" id="navbarContent">

                {{-- Left: main nav links (authenticated) --}}
                <ul class="navbar-nav me-auto">
                    @auth
                        @if(Auth::user()->isAdmin())
                            <li class="nav-item">
                                <a class="nav-link {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}"
                                   href="{{ route('admin.dashboard') }}">
                                    <i class="fa fa-tachometer-alt me-1"></i>Dashboard
                                </a>
                            </li>
                            <li class="nav-item dropdown">
                                <a class="nav-link dropdown-toggle {{ request()->routeIs('admin.*') && !request()->routeIs('admin.dashboard') ? 'active' : '' }}"
                                   href="#" role="button" data-bs-toggle="dropdown">
                                    <i class="fa fa-cogs me-1"></i>Admin
                                </a>
                                <ul class="dropdown-menu">
                                    <li>
                                        <a class="dropdown-item" href="{{ route('admin.users') }}">
                                            <i class="fa fa-users me-1"></i>Users
                                        </a>
                                    </li>
                                </ul>
                            </li>
                        @else
                            <li class="nav-item">
                                <a class="nav-link {{ request()->routeIs('dashboard') ? 'active' : '' }}"
                                   href="{{ route('dashboard') }}">
                                    <i class="fa fa-home me-1"></i>Dashboard
                                </a>
                            </li>
                        @endif

                        {{-- Samples dropdown (mobile gets New Sample too) --}}
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle {{ request()->routeIs('samples.*') ? 'active' : '' }}"
                               href="#" role="button" data-bs-toggle="dropdown">
                                <i class="fa fa-flask me-1"></i>Samples
                            </a>
                            <ul class="dropdown-menu">
                                <li>
                                    <a class="dropdown-item" href="{{ route('samples.index') }}">
                                        <i class="fa fa-list me-1"></i>All Samples
                                    </a>
                                </li>
                                <li>
                                    <a class="dropdown-item" href="{{ route('farms.index') }}">
                                        <i class="fa fa-plus-circle me-1"></i>New Analysis
                                    </a>
                                </li>
                            </ul>
                        </li>

                        <li class="nav-item">
                            <a class="nav-link {{ request()->routeIs('crops.*') ? 'active' : '' }}"
                               href="{{ route('crops.index') }}">
                                <i class="fa fa-seedling me-1"></i>Crops
                            </a>
                        </li>

                        {{-- Farmers dropdown --}}
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle {{ request()->routeIs('farmers.*') ? 'active' : '' }}"
                               href="#" role="button" data-bs-toggle="dropdown">
                                <i class="fa fa-user-tie me-1"></i>Farmers
                            </a>
                            <ul class="dropdown-menu">
                                <li>
                                    <a class="dropdown-item" href="{{ route('farmers.index') }}">
                                        <i class="fa fa-users me-1"></i>All Farmers
                                    </a>
                                </li>
                                <li>
                                    <a class="dropdown-item" href="{{ route('farmers.create') }}">
                                        <i class="fa fa-user-plus me-1"></i>Register Farmer
                                    </a>
                                </li>
                            </ul>
                        </li>

                        {{-- Farms dropdown --}}
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle {{ request()->routeIs('farms.*', 'barangays.*') ? 'active' : '' }}"
                               href="#" role="button" data-bs-toggle="dropdown">
                                <i class="fa fa-tractor me-1"></i>Farms
                            </a>
                            <ul class="dropdown-menu">
                                <li>
                                    <a class="dropdown-item" href="{{ route('farms.index') }}">
                                        <i class="fa fa-map-marked-alt me-1"></i>All Farms
                                    </a>
                                </li>
                                <li>
                                    <a class="dropdown-item" href="{{ route('barangays.index') }}">
                                        <i class="fa fa-city me-1"></i>Barangays
                                    </a>
                                </li>
                                <li>
                                    <a class="dropdown-item" href="{{ route('public.map') }}">
                                        <i class="fa fa-map me-1"></i>Public Map
                                    </a>
                                </li>
                            </ul>
                        </li>

                        {{-- Export dropdown --}}
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle {{ request()->routeIs('export*') ? 'active' : '' }}"
                               href="#" role="button" data-bs-toggle="dropdown">
                                <i class="fa fa-file-csv me-1"></i>Export
                            </a>
                            <ul class="dropdown-menu">
                                <li>
                                    <a class="dropdown-item" href="{{ route('export') }}">
                                        <i class="fa fa-file-csv me-1"></i>Full Export
                                    </a>
                                </li>
                                <li>
                                    <a class="dropdown-item" href="{{ route('export.phase2') }}">
                                        <i class="fa fa-microchip me-1"></i>Phase 2 Export
                                    </a>
                                </li>
                            </ul>
                        </li>

                        <li class="nav-item">
                            <a class="nav-link {{ request()->routeIs('help.*') ? 'active' : '' }}"
                               href="{{ route('help.index') }}">
                                <i class="fa fa-circle-question me-1"></i>Help
                            </a>
                        </li>
                    @endauth
                </ul>

                {{-- Right: user menu / guest links --}}
                <ul class="navbar-nav ms-auto align-items-center">
                    @auth
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                                <i class="fa fa-user-circle me-1"></i>{{ Auth::user()->username }}
                            </a>
                            <ul class="dropdown-menu dropdown-menu-end">
                                <li>
                                    <a class="dropdown-item text-danger" href="#"
                                       onclick="event.preventDefault(); document.getElementById('logout-form').submit();">
                                        <i class="fa fa-sign-out-alt me-1"></i> Logout
                                    </a>
                                    <form id="logout-form" action="{{ route('logout') }}" method="POST" class="d-none">
                                        @csrf
                                    </form>
                                </li>
                            </ul>
                        </li>
                    @else
                        <li class="nav-item">
                            <a class="nav-link" href="{{ route('login') }}">Login</a>
                        </li>
                    @endauth
                </ul>

            </div>
        </div>
    </nav>

    {{-- ── Body: sidebar + main ────────────────────────────────── --}}
    <div class="app-body">

        @auth
        {{-- Sidebar --}}
        <nav class="sidebar d-none d-md-block">
            <div class="sidebar-user">
                <div class="sidebar-user-avatar">{{ strtoupper(substr(Auth::user()->username, 0, 1)) }}</div>
                <div>
                    <p class="sidebar-user-name">{{ Auth::user()->username }}</p>
                    <span class="sidebar-user-role">{{ Auth::user()->isAdmin() ? 'Administrator' : 'Technician' }}</span>
                </div>
            </div>
            <ul class="nav flex-column">

                @if(Auth::user()->isAdmin())
                    <li><span class="sidebar-section">Admin</span></li>
                    <li class="nav-item">
                        <a class="nav-link {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}"
                           href="{{ route('admin.dashboard') }}">
                            <i class="fa fa-tachometer-alt"></i> Dashboard
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link {{ request()->routeIs('admin.users') ? 'active' : '' }}"
                           href="{{ route('admin.users') }}">
                            <i class="fa fa-users"></i> Users
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link {{ request()->routeIs('admin.ph-color-charts') ? 'active' : '' }}"
                           href="{{ route('admin.ph-color-charts') }}">
                            <i class="fa fa-palette"></i> pH Color Charts
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link {{ request()->routeIs('admin.npk-color-charts') ? 'active' : '' }}"
                           href="{{ route('admin.npk-color-charts') }}">
                            <i class="fa fa-seedling"></i> NPK Color Charts
                        </a>
                    </li>
                    <li><div class="sidebar-divider"></div></li>
                @else
                    <li><span class="sidebar-section">Menu</span></li>
                    <li class="nav-item">
                        <a class="nav-link {{ request()->routeIs('dashboard') ? 'active' : '' }}"
                           href="{{ route('dashboard') }}">
                            <i class="fa fa-home"></i> Dashboard
                        </a>
                    </li>
                    <li><div class="sidebar-divider"></div></li>
                @endif

                <li><span class="sidebar-section">Samples</span></li>
                <li class="nav-item">
                    <a class="nav-link {{ request()->routeIs('samples.index') ? 'active' : '' }}"
                       href="{{ route('samples.index') }}">
                        <i class="fa fa-flask"></i> Soil Samples
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link {{ request()->routeIs('analyses.*') ? 'active' : '' }}"
                       href="{{ route('farms.index') }}">
                        <i class="fa fa-plus-circle"></i> New Analysis
                    </a>
                </li>

                <li class="nav-item">
                    <a class="nav-link {{ request()->routeIs('crops.index', 'crops.create', 'crops.edit') ? 'active' : '' }}"
                       href="{{ route('crops.index') }}">
                        <i class="fa fa-seedling"></i> Crops
                    </a>
                </li>
                <li class="nav-item d-none">
                    <a class="nav-link {{ request()->routeIs('crops.requirements*') ? 'active' : '' }}"
                       href="{{ route('crops.requirements') }}">
                        <i class="fa fa-leaf"></i> Crop Requirements
                    </a>
                </li>

                <li><div class="sidebar-divider"></div></li>
                <li><span class="sidebar-section">Farmers &amp; Farms</span></li>
                <li class="nav-item">
                    <a class="nav-link {{ request()->routeIs('farmers.*') ? 'active' : '' }}"
                       href="{{ route('farmers.index') }}">
                        <i class="fa fa-user-tie"></i> Farmers
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link {{ request()->routeIs('farms.*') ? 'active' : '' }}"
                       href="{{ route('farms.index') }}">
                        <i class="fa fa-tractor"></i> Farms
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link {{ request()->routeIs('barangays.*') ? 'active' : '' }}"
                       href="{{ route('barangays.index') }}">
                        <i class="fa fa-city"></i> Barangays
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link {{ request()->routeIs('public.map') ? 'active' : '' }}"
                       href="{{ route('public.map') }}">
                        <i class="fa fa-map"></i> Public Map
                    </a>
                </li>

                <li><div class="sidebar-divider"></div></li>
                <li><span class="sidebar-section">Export</span></li>
                <li class="nav-item">
                    <a class="nav-link {{ request()->routeIs('export') && !request()->routeIs('export.phase2') ? 'active' : '' }}"
                       href="{{ route('export') }}">
                        <i class="fa fa-file-csv"></i> Full Export
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link {{ request()->routeIs('export.phase2') ? 'active' : '' }}"
                       href="{{ route('export.phase2') }}">
                        <i class="fa fa-microchip"></i> Phase 2 Export
                    </a>
                </li>

                <li><div class="sidebar-divider"></div></li>
                <li><span class="sidebar-section">Support</span></li>
                <li class="nav-item">
                    <a class="nav-link {{ request()->routeIs('help.index') ? 'active' : '' }}"
                       href="{{ route('help.index') }}">
                        <i class="fa fa-circle-question"></i> Help &amp; Guidelines
                    </a>
                </li>

            </ul>
        </nav>
        @endauth

        {{-- Main content --}}
        <main class="main-content">

            {{-- Flash messages --}}
            @if(session('success'))
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="fa fa-check-circle me-1"></i> {{ session('success') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif
            @if(session('error'))
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="fa fa-exclamation-circle me-1"></i> {{ session('error') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif

            @yield('content')
        </main>

    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    @yield('scripts')
</body>
</html>
