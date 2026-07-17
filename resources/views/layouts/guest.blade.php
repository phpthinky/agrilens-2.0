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
        html, body { height: 100%; }
        body {
            font-family: 'Inter', 'Segoe UI', system-ui, sans-serif;
            display: flex;
        }

        .auth-shell { display: flex; min-height: 100vh; width: 100%; }

        /* ── Left: brand / hero panel ─────────────────────────────── */
        .auth-hero {
            flex: 1 1 46%;
            background:
                radial-gradient(circle at 15% 20%, rgba(255,255,255,.06) 0, transparent 45%),
                radial-gradient(circle at 85% 80%, rgba(255,255,255,.05) 0, transparent 40%),
                linear-gradient(155deg, var(--agri-900), var(--agri-700) 60%, var(--agri-600));
            color: #fff;
            padding: 3.5rem 3.25rem;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            position: relative;
            overflow: hidden;
        }
        .auth-hero::after {
            content: '';
            position: absolute; inset: 0;
            background-image: radial-gradient(circle at 1px 1px, rgba(255,255,255,.07) 1px, transparent 0);
            background-size: 26px 26px;
            pointer-events: none;
        }
        .auth-hero > * { position: relative; z-index: 1; }

        .auth-brand { display: flex; align-items: center; gap: .75rem; }
        .auth-brand .brand-mark {
            width: 46px; height: 46px; border-radius: 12px;
            background: rgba(255,255,255,.15);
            border: 1px solid rgba(255,255,255,.25);
            display: flex; align-items: center; justify-content: center;
            font-size: 1.35rem; color: #d4f5d8;
        }
        .auth-brand-name { font-weight: 800; font-size: 1.3rem; letter-spacing: -.02em; }
        .auth-brand-tag { font-size: .75rem; color: rgba(255,255,255,.65); font-weight: 500; }

        .auth-hero-title {
            font-size: 2rem;
            font-weight: 800;
            line-height: 1.2;
            letter-spacing: -.02em;
            max-width: 30ch;
            margin: 2.5rem 0 1rem;
        }
        .auth-hero-lead {
            color: rgba(255,255,255,.8);
            font-size: .975rem;
            max-width: 40ch;
            margin-bottom: 2rem;
        }
        .auth-feature-list { display: flex; flex-direction: column; gap: .9rem; }
        .auth-feature {
            display: flex; align-items: flex-start; gap: .75rem;
            font-size: .875rem; color: rgba(255,255,255,.9);
        }
        .auth-feature i {
            width: 30px; height: 30px; border-radius: 8px; flex-shrink: 0;
            background: rgba(255,255,255,.12);
            display: flex; align-items: center; justify-content: center;
            color: var(--agri-amber);
        }
        .auth-hero-footer {
            font-size: .78rem;
            color: rgba(255,255,255,.55);
        }

        /* ── Right: form panel ────────────────────────────────────── */
        .auth-form-panel {
            flex: 1 1 54%;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #f6f8f5;
            padding: 2rem;
        }
        .auth-form-wrap { width: 100%; max-width: 400px; }
        .auth-form-header { margin-bottom: 1.75rem; }
        .auth-form-header h1 {
            font-size: 1.5rem; font-weight: 800; color: #14251a;
            letter-spacing: -.02em; margin-bottom: .25rem;
        }
        .auth-form-header p { color: #6b7d6c; font-size: .9rem; margin: 0; }

        .auth-card {
            background: #fff;
            border-radius: 14px;
            box-shadow: 0 1px 3px rgba(15,61,26,.06), 0 12px 32px rgba(15,61,26,.08);
            padding: 1.75rem;
        }

        .form-label { font-weight: 600; font-size: .85rem; color: #33423a; }
        .form-control {
            border-radius: 8px;
            border-color: #dbe4dc;
            padding: .6rem .85rem;
        }
        .form-control:focus {
            border-color: var(--agri-500);
            box-shadow: 0 0 0 .2rem rgba(56,142,60,.15);
        }
        .btn { border-radius: 8px; font-weight: 600; }
        .btn-success {
            background: linear-gradient(100deg, var(--agri-600), var(--agri-500));
            border: none;
            padding: .65rem 1rem;
        }
        .btn-success:hover { background: linear-gradient(100deg, var(--agri-700), var(--agri-600)); }

        .auth-back-link {
            display: inline-flex; align-items: center; gap: .4rem;
            font-size: .85rem; color: #557857; text-decoration: none; font-weight: 500;
            margin-top: 1.5rem;
        }
        .auth-back-link:hover { color: var(--agri-600); }

        @media (max-width: 900px) {
            .auth-hero { display: none; }
            .auth-form-panel { flex: 1 1 100%; }
        }

        @yield('styles')
    </style>
</head>
<body>
    <div class="auth-shell">
        <div class="auth-hero">
            <div class="auth-brand">
                <span class="brand-mark"><i class="fa fa-seedling"></i></span>
                <div>
                    <div class="auth-brand-name">{{ config('app.name', 'AgriLens 2.0') }}</div>
                    <div class="auth-brand-tag">Office of the Municipal Agriculturist — Sablayan</div>
                </div>
            </div>

            <div>
                <div class="auth-hero-title">Soil intelligence for a stronger harvest</div>
                <p class="auth-hero-lead">
                    Manual, colorimetric, and Digital Probe QR soil analysis — with GPS farm mapping and
                    fertilizer recommendations — in one platform built for Sablayan's field technicians.
                </p>
                <div class="auth-feature-list">
                    <div class="auth-feature">
                        <i class="fa fa-flask"></i>
                        <div>Three soil test modes: manual entry, webcam colorimetric capture, and Digital Probe QR scan</div>
                    </div>
                    <div class="auth-feature">
                        <i class="fa fa-map-marked-alt"></i>
                        <div>GPS farm boundary mapping with automatic area and fertility visualization</div>
                    </div>
                    <div class="auth-feature">
                        <i class="fa fa-seedling"></i>
                        <div>Automated fertility scoring, crop matching, and BSWM/PhilRice fertilizer schedules</div>
                    </div>
                </div>
            </div>

            <div class="auth-hero-footer">
                &copy; {{ date('Y') }} Sablayan Municipality, Occidental Mindoro. All rights reserved.
            </div>
        </div>

        <div class="auth-form-panel">
            <div class="auth-form-wrap">
                @yield('content')

                <a href="{{ route('public.map') }}" class="auth-back-link">
                    <i class="fa fa-arrow-left"></i> Back to public map
                </a>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    @yield('scripts')
</body>
</html>
