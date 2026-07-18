<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    
    <title>@hasSection('title')@yield('title') — @endif{{ config('app.name', 'AgriLens 2.0') }}</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" rel="stylesheet">
    
    <!-- Custom Layout CSS -->
    <style>
        :root {
            --forest-green: #17501f;
            --forest-green-dark: #0f3d1a;
            --forest-green-light: #2e7d32;
            --agri-amber: #f9a825;
            --agri-bg: #f8f9fa;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: var(--agri-bg);
            -webkit-font-smoothing: antialiased;
        }
        
        .sidebar {
            background-color: var(--forest-green) !important;
            min-height: 100vh;
            max-height: 100vh;
            width: 260px;
            position: fixed;
            top: 0;
            left: 0;
            z-index: 1000;
            transition: all 0.3s;
            overflow-y: auto !important;
            overflow-x: hidden;
        }
        
        .sidebar.collapsed {
            margin-left: -260px;
        }
        
        /* Sidebar Scrollbar Styling */
        .sidebar::-webkit-scrollbar {
            width: 10px;
            background-color: rgba(0, 0, 0, 0.2);
        }
        
        .sidebar::-webkit-scrollbar-track {
            background-color: rgba(0, 0, 0, 0.2);
            border-radius: 0;
        }
        
        .sidebar::-webkit-scrollbar-thumb {
            background-color: rgba(255, 255, 255, 0.6);
            border-radius: 5px;
            border: 2px solid rgba(0, 0, 0, 0.2);
        }
        
        .sidebar::-webkit-scrollbar-thumb:hover {
            background-color: rgba(255, 255, 255, 0.8);
        }
        
        .sidebar {
            scrollbar-width: auto;
            scrollbar-color: rgba(255, 255, 255, 0.6) rgba(0, 0, 0, 0.2);
        }
        
        @media (min-width: 769px) {
            .sidebar {
                overflow-y: scroll !important;
            }
        }
        
        .sidebar .nav-link {
            color: rgba(255, 255, 255, 0.9) !important;
            padding: 12px 20px;
            border-radius: 0;
            transition: all 0.3s;
            font-size: 0.95rem;
            font-weight: 500;
        }
        
        .sidebar .nav-link:hover {
            background-color: var(--forest-green-dark);
            color: white !important;
            padding-left: 25px;
        }
        
        .sidebar .nav-link.active {
            background-color: var(--forest-green-light);
            color: white !important;
            border-left: 4px solid var(--agri-amber);
        }
        
        .sidebar-header {
            color: rgba(255, 255, 255, 0.5);
            padding: 20px 20px 5px 20px;
            font-size: 0.75rem;
            text-transform: uppercase;
            letter-spacing: 1px;
            font-weight: 700;
            margin-top: 10px;
        }
        
        .sidebar-header:first-of-type {
            margin-top: 0;
        }
        
        .main-content {
            margin-left: 260px;
            transition: margin-left 0.3s;
            min-height: 100vh;
        }
        
        .main-content.expanded {
            margin-left: 0;
        }
        
        .top-navbar {
            background-color: white;
            box-shadow: 0 2px 4px rgba(0,0,0,0.08);
            padding: 15px 25px;
            margin-bottom: 25px;
            position: sticky;
            top: 0;
            z-index: 999;
        }
        
        .sidebar-brand {
            padding: 25px 20px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.15);
        }
        
        .sidebar-brand h4 {
            color: white;
            margin: 0;
            font-size: 1.2rem;
            font-weight: 800;
            letter-spacing: -.02em;
        }
        
        .sidebar-brand small {
            color: rgba(255, 255, 255, 0.7);
            display: block;
            margin-top: 6px;
            font-size: 0.75rem;
            line-height: 1.3;
        }

        .sidebar-user-section {
            padding: 15px 20px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.15);
            color: white;
            display: flex;
            align-items: center;
            gap: .65rem;
        }

        .sidebar-user-avatar {
            width: 36px; height: 36px; border-radius: 50%;
            background: rgba(255,255,255,.15);
            border: 1px solid rgba(255,255,255,.2);
            display: flex; align-items: center; justify-content: center;
            font-weight: 700; color: #fff; font-size: .95rem;
            flex-shrink: 0;
        }
        
        @media (max-width: 768px) {
            .sidebar {
                margin-left: -260px;
            }
            
            .sidebar.show {
                margin-left: 0;
            }
            
            .main-content {
                margin-left: 0;
            }
        }
        
        .content-wrapper {
            padding: 0 25px 25px 25px;
        }
        
        .card {
            box-shadow: 0 1px 3px rgba(15,61,26,.06), 0 6px 18px rgba(15,61,26,.06);
            border: none;
            border-radius: 8px;
        }
        
        @yield('styles')
    </style>
</head>
<body>

    <!-- Sidebar Layout -->
    <nav class="sidebar" id="sidebar">
        <div class="sidebar-brand">
            <h4><i class="fa fa-seedling me-1"></i> {{ config('app.name', 'AgriLens') }}</h4>
            <small>Soil Fertility &amp; Fertilizer Advisory System</small>
        </div>
        
        @auth
            <!-- Sidebar Authenticated User Info Block -->
            <div class="sidebar-user-section">
                <div class="sidebar-user-avatar">{{ strtoupper(substr(Auth::user()->username, 0, 1)) }}</div>
                <div>
                    <div class="fw-bold" style="font-size: 0.875rem; line-height: 1.2;">{{ Auth::user()->username }}</div>
                    <small style="color: var(--agri-amber); font-size: 0.7rem; font-weight: 600; text-transform: uppercase;">
                        {{ Auth::user()->isAdmin() ? 'Administrator' : 'Technician' }}
                    </small>
                </div>
            </div>

            <!-- Custom Unified Navigation Options Mapping -->
            <ul class="nav flex-column mt-2">
                @if(Auth::user()->isAdmin())
                    <li class="sidebar-header">Admin Engine</li>
                    <li class="nav-item">
                        <a class="nav-link {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}" href="{{ route('admin.dashboard') }}">
                            <i class="fa fa-tachometer-alt me-2"></i> Dashboard
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link {{ request()->routeIs('admin.users') ? 'active' : '' }}" href="{{ route('admin.users') }}">
                            <i class="fa fa-users me-2"></i> Users
                        </a>
                    </li>
                    
                @else
                    <li class="sidebar-header">Main Panel</li>
                    <li class="nav-item">
                        <a class="nav-link {{ request()->routeIs('dashboard') ? 'active' : '' }}" href="{{ route('dashboard') }}">
                            <i class="fa fa-home me-2"></i> Dashboard
                        </a>
                    </li>
                @endif

                <li class="sidebar-header">Soil Diagnostics</li>
                <li class="nav-item">
                    <a class="nav-link {{ request()->routeIs('samples.index') ? 'active' : '' }}" href="{{ route('samples.index') }}">
                        <i class="fa fa-flask me-2"></i> Soil Samples
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link {{ request()->routeIs('analyses.*') ? 'active' : '' }}" href="{{ route('farms.index') }}">
                        <i class="fa fa-plus-circle me-2"></i> New Analysis
                    </a>
                </li>

                <li class="sidebar-header">Registry Information</li>
                <li class="nav-item">
                    <a class="nav-link {{ request()->routeIs('farmers.*') ? 'active' : '' }}" href="{{ route('farmers.index') }}">
                        <i class="fa fa-user-tie me-2"></i> Farmers
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link {{ request()->routeIs('farms.*') ? 'active' : '' }}" href="{{ route('farms.index') }}">
                        <i class="fa fa-tractor me-2"></i> Farms
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link {{ request()->routeIs('barangays.*') ? 'active' : '' }}" href="{{ route('barangays.index') }}">
                        <i class="fa fa-city me-2"></i> Barangays
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link {{ request()->routeIs('public.map') ? 'active' : '' }}" href="{{ route('public.map') }}">
                        <i class="fa fa-map me-2"></i> Public Map
                    </a>
                </li>

                <li class="sidebar-header">Data Management</li>
                <li class="nav-item">
                    <a class="nav-link {{ request()->routeIs('export') ? 'active' : '' }}" href="{{ route('export') }}">
                        <i class="fa fa-file-csv me-2"></i> Full Export
                    </a>
                </li>
                <li class="sidebar-header">System Operations</li>

                <li class="nav-item">
                        <a class="nav-link {{ request()->routeIs('admin.ph-color-charts') ? 'active' : '' }}" href="{{ route('admin.ph-color-charts') }}">
                            <i class="fa fa-palette me-2"></i> pH Color Charts
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link {{ request()->routeIs('admin.npk-color-charts') ? 'active' : '' }}" href="{{ route('admin.npk-color-charts') }}">
                            <i class="fa fa-seedling me-2"></i> NPK Color Charts
                        </a>
                    </li>
                <li class="nav-item">
                    <a class="nav-link {{ request()->routeIs('crops.index', 'crops.create', 'crops.edit') ? 'active' : '' }}" href="{{ route('crops.index') }}">
                        <i class="fa fa-seedling me-2"></i> Crops
                    </a>
                </li>

                <li class="nav-item">
                    <a class="nav-link {{ request()->routeIs('help.index') ? 'active' : '' }}" href="{{ route('help.index') }}">
                        <i class="fa fa-circle-question me-2"></i> Help &amp; Guidelines
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="#" onclick="event.preventDefault(); document.getElementById('logout-form').submit();">
                        <i class="fa fa-sign-out-alt me-2"></i> Logout
                    </a>
                    <form id="logout-form" action="{{ route('logout') }}" method="POST" class="d-none">
                        @csrf
                    </form>
                </li>
            </ul>
        @else
            <ul class="nav flex-column mt-3">
                <li class="nav-item">
                    <a class="nav-link" href="{{ route('login') }}"><i class="fa fa-sign-in-alt me-2"></i> Login</a>
                </li>
            </ul>
        @endauth
    </nav>
    
    <!-- Main Window Container -->
    <div class="main-content" id="main-content">
        <!-- Persistent Top Navigation Frame -->
        <div class="top-navbar d-flex justify-content-between align-items-center">
            <div class="d-flex align-items-center">
                <button class="btn btn-link p-0 me-3 text-decoration-none" id="sidebarToggle">
                    <i class="fas fa-bars fa-lg text-secondary"></i>
                </button>
                <h5 class="mb-0 text-secondary fw-bold">@yield('page-title', 'Dashboard')</h5>
            </div>
            
            <div class="d-flex align-items-center">
                @auth
                    <span class="text-muted me-3 d-none d-md-inline">
                        <i class="fas fa-user-circle me-1"></i> {{ Auth::user()->username }}
                    </span>
                    <span class="badge bg-success">{{ Auth::user()->isAdmin() ? 'Admin' : 'Technician' }}</span>
                @else
                    <a href="{{ route('login') }}" class="btn btn-sm btn-outline-secondary">Login</a>
                @endauth
            </div>
        </div>
        
        <!-- Application View Context -->
        <div class="content-wrapper">
            {{-- Flash Messages Framework --}}
            @if(session('success'))
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="fa fa-check-circle me-2"></i> {{ session('success') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif
            @if(session('error'))
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="fa fa-exclamation-circle me-2"></i> {{ session('error') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif
            
            <!-- Yield Main Context -->
            @yield('content')
        </div>
    </div>
    
    <!-- Bootstrap JS Module -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- Frame Logic Handler Script -->
    <script>
        // Sidebar runtime toggle functionality
        document.getElementById('sidebarToggle').addEventListener('click', function() {
            const sidebar = document.getElementById('sidebar');
            const mainContent = document.getElementById('main-content');
            
            if (window.innerWidth > 768) {
                sidebar.classList.toggle('collapsed');
                mainContent.classList.toggle('expanded');
            } else {
                sidebar.classList.toggle('show');
            }
        });
        
        // Handle framework adjustment on resize
        window.addEventListener('resize', function() {
            const sidebar = document.getElementById('sidebar');
            const mainContent = document.getElementById('main-content');
            
            if (window.innerWidth > 768) {
                sidebar.classList.remove('show');
            } else {
                sidebar.classList.remove('collapsed');
                mainContent.classList.remove('expanded');
            }
        });
        
        // Auto-dismiss layout alerts after 5 seconds
        setTimeout(function() {
            const alerts = document.querySelectorAll('.alert');
            alerts.forEach(alert => {
                const bsAlert = new bootstrap.Alert(alert);
                bsAlert.close();
            });
        }, 5000);
    </script>
    
    @yield('scripts')
</body>
</html>