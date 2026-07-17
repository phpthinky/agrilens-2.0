<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>@hasSection('title')@yield('title') — @endif{{ config('app.name', 'AgriLens 2.0') }}</title>

    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700,800" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" rel="stylesheet">

    @stack('map-styles')

    <style>
        :root {
            --agri-900: #0f3d1a;
            --agri-800: #17501f;
            --agri-700: #1f6b2c;
            --agri-600: #2e7d32;
            --agri-500: #388e3c;
            --agri-100: #e8f5e9;
            --agri-amber: #f9a825;
        }
        * { -webkit-font-smoothing: antialiased; }
        body {
            font-family: 'Inter', 'Segoe UI', system-ui, sans-serif;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            background-color: #f6f8f5;
        }
        .main-content { flex: 1; }

        /* ── Public navbar ───────────────────────────────────────── */
        .public-navbar {
            background: linear-gradient(100deg, var(--agri-900), var(--agri-700)) !important;
            box-shadow: 0 2px 10px rgba(15,61,26,.3);
        }
        .public-navbar .navbar-brand {
            font-weight: 800;
            letter-spacing: -.02em;
            display: flex; align-items: center; gap: .6rem;
            color: #fff !important;
        }
        .public-navbar .brand-mark {
            width: 34px; height: 34px; border-radius: 9px;
            background: rgba(255,255,255,.16);
            border: 1px solid rgba(255,255,255,.25);
            display: flex; align-items: center; justify-content: center;
            color: #d4f5d8; font-size: 1rem;
        }
        .public-navbar .nav-link { color: rgba(255,255,255,.85) !important; font-weight: 500; }
        .public-navbar .nav-link:hover,
        .public-navbar .nav-link.active { color: #fff !important; }
        .public-navbar .nav-link.active { border-bottom: 2px solid var(--agri-amber); }
        .public-navbar .btn-staff-login {
            background: rgba(255,255,255,.14);
            border: 1px solid rgba(255,255,255,.3);
            color: #fff; font-weight: 600; font-size: .875rem;
            border-radius: 7px; padding: .4rem 1rem;
        }
        .public-navbar .btn-staff-login:hover { background: rgba(255,255,255,.24); color: #fff; }

        /* ── Cards / buttons (shared look with app) ──────────────── */
        .card {
            border: none;
            box-shadow: 0 1px 3px rgba(15,61,26,.06), 0 6px 18px rgba(15,61,26,.06);
            border-radius: .625rem;
        }
        .btn { border-radius: 7px; font-weight: 500; }
        .btn-success { background-color: var(--agri-500); border-color: var(--agri-600); }
        .btn-success:hover { background-color: var(--agri-600); border-color: var(--agri-700); }

        /* ── Footer ──────────────────────────────────────────────── */
        footer.site-footer {
            background: var(--agri-900);
            color: rgba(255,255,255,.75);
            margin-top: auto;
        }
        footer.site-footer h5, footer.site-footer h6 { color: #fff; }
        footer.site-footer a { color: rgba(255,255,255,.6); text-decoration: none; }
        footer.site-footer a:hover { color: #fff; }

        @yield('styles')
    </style>
</head>
<body>
    {{-- ── Public Navbar ───────────────────────────────────────── --}}
    <nav class="navbar navbar-expand-lg public-navbar sticky-top">
        <div class="container">
            <a class="navbar-brand" href="{{ route('public.map') }}">
                <span class="brand-mark"><i class="fa fa-seedling"></i></span>
                {{ config('app.name', 'AgriLens 2.0') }}
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#publicNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="publicNav">
                <ul class="navbar-nav ms-auto align-items-lg-center">
                    <li class="nav-item">
                        <a class="nav-link {{ request()->routeIs('public.map') ? 'active' : '' }}" href="{{ route('public.map') }}">
                            <i class="fas fa-map me-1"></i> Interactive Map
                        </a>
                    </li>

                    @auth
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                                <i class="fas fa-user-circle me-1"></i>{{ Auth::user()->username }}
                            </a>
                            <ul class="dropdown-menu dropdown-menu-end">
                                <li>
                                    <a class="dropdown-item" href="{{ route(Auth::user()->isAdmin() ? 'admin.dashboard' : 'dashboard') }}">
                                        <i class="fas fa-tachometer-alt me-1"></i> Dashboard
                                    </a>
                                </li>
                                <li><hr class="dropdown-divider"></li>
                                <li>
                                    <a class="dropdown-item text-danger" href="#"
                                       onclick="event.preventDefault(); document.getElementById('public-logout-form').submit();">
                                        <i class="fas fa-sign-out-alt me-1"></i> Logout
                                    </a>
                                    <form id="public-logout-form" action="{{ route('logout') }}" method="POST" class="d-none">@csrf</form>
                                </li>
                            </ul>
                        </li>
                    @else
                        <li class="nav-item ms-lg-2 mt-2 mt-lg-0">
                            <a class="btn btn-staff-login" href="{{ route('login') }}">
                                <i class="fas fa-sign-in-alt me-1"></i> Staff Login
                            </a>
                        </li>
                    @endauth
                </ul>
            </div>
        </div>
    </nav>

    {{-- ── Main Content ────────────────────────────────────────── --}}
    <main class="main-content">
        @yield('content')
    </main>

    {{-- ── Footer ──────────────────────────────────────────────── --}}
    <footer class="site-footer py-4 mt-5">
        <div class="container">
            <div class="row gy-4">
                <div class="col-md-6">
                    <h5><i class="fas fa-seedling me-2"></i>{{ config('app.name', 'AgriLens 2.0') }}</h5>
                    <p class="mb-0 small">
                        <i class="fas fa-map-marker-alt me-1"></i>
                        Office of the Municipal Agriculturist<br>
                        Sablayan, Occidental Mindoro, Philippines
                    </p>
                </div>
                <div class="col-md-3">
                    <h6>Quick Links</h6>
                    <ul class="list-unstyled small">
                        <li><a href="{{ route('public.map') }}">Interactive Map</a></li>
                        @guest
                            <li><a href="{{ route('login') }}">Staff Login</a></li>
                        @endguest
                    </ul>
                </div>
                <div class="col-md-3">
                    <h6>About</h6>
                    <p class="small mb-0">
                        Multi-modal soil analysis — manual, colorimetric, and Digital Probe QR scan —
                        with GPS farm mapping and fertilizer recommendations.
                    </p>
                </div>
            </div>
            <hr style="border-color: rgba(255,255,255,.12);">
            <div class="text-center small">
                &copy; {{ date('Y') }} Sablayan Municipality. All rights reserved.
            </div>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    @yield('scripts')
</body>
</html>
