<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Report Studio — Dashboard Laporan Les</title>
    <link rel="stylesheet" href="{{ asset('css/app-custom.css') }}">

    {{-- NProgress — slim page-top loading bar --}}
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/nprogress@0.2.0/nprogress.css">

    {{-- Tom Select — premium custom dropdowns --}}
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/tom-select@2.3.1/dist/css/tom-select.min.css">
    <style>
        /* ── Tom Select skin override to match app design ─────────────── */
        .ts-wrapper.single .ts-control,
        .ts-wrapper .ts-control {
            background: #FCFAF6;
            border: 1.5px solid var(--line, #E4DCCE);
            border-radius: 8px;
            padding: 10px 12px;
            font-size: 14px;
            font-family: inherit;
            color: var(--ink, #1B2A41);
            box-shadow: none;
            min-height: unset;
            cursor: pointer;
            transition: border-color .15s, box-shadow .15s;
        }
        .ts-wrapper.single.focus .ts-control,
        .ts-wrapper.multi.focus .ts-control {
            border-color: var(--teal, #2F8F7E);
            box-shadow: 0 0 0 3px color-mix(in srgb, var(--teal, #2F8F7E) 15%, transparent);
            outline: none;
        }
        .ts-wrapper.single .ts-control::after {
            border-color: var(--muted, #7C7568) transparent transparent transparent;
        }
        .ts-wrapper.single.dropdown-active .ts-control::after {
            border-color: transparent transparent var(--muted, #7C7568) transparent;
        }
        .ts-dropdown {
            background: #FFFFFF;
            border: 1.5px solid var(--line, #E4DCCE);
            border-radius: 8px;
            box-shadow: 0 8px 24px rgba(27,42,65,0.08);
            margin-top: 4px;
            font-family: inherit;
            font-size: 14px;
            overflow: hidden;
            opacity: 0;
            transform: translateY(-6px);
            transition: opacity 0.15s ease, transform 0.15s ease;
        }
        .ts-wrapper.dropdown-active .ts-dropdown {
            opacity: 1;
            transform: translateY(0);
        }
        .ts-dropdown .option {
            padding: 10px 14px;
            color: var(--ink, #1B2A41);
            cursor: pointer;
            border-radius: 0;
            transition: background .1s;
        }
        .ts-dropdown .option:hover,
        .ts-dropdown .option.active {
            background: color-mix(in srgb, var(--teal, #2F8F7E) 8%, transparent);
            color: var(--teal, #2F8F7E);
        }
        .ts-dropdown .option.selected {
            font-weight: 600;
            background: color-mix(in srgb, var(--teal, #2F8F7E) 12%, transparent);
            color: var(--teal, #2F8F7E);
        }
        .ts-dropdown-content { padding: 4px 0; }
        .ts-wrapper .ts-control input { color: var(--ink, #1B2A41); }
        .ts-wrapper .ts-control .placeholder { color: var(--muted, #7C7568); }
    </style>

    {{-- MCDatepicker — premium Material Design date picker --}}
    <link href="https://cdn.jsdelivr.net/npm/mc-datepicker/dist/mc-calendar.min.css" rel="stylesheet" />
    <style>
        /* ── MCDatepicker custom style to match theme ─────────────────── */
        .mc-calendar {
            font-family: 'Inter', sans-serif !important;
            border-radius: 12px !important;
            box-shadow: 0 10px 30px rgba(27,42,65,0.08) !important;
            border: 1px solid var(--line, #E4DCCE) !important;
            background-color: #FFFFFF !important;
        }
        .mc-calendar--inline {
            display: none;
            position: fixed !important;
            z-index: 9999 !important;
            margin-top: 0 !important;
        }
        .mc-calendar .mc-display {
            background-color: var(--teal, #2F8F7E) !important;
            padding: 20px !important;
            border-top-left-radius: 10px !important;
            border-top-right-radius: 10px !important;
        }
        .mc-calendar .mc-display__day {
            font-family: 'Space Grotesk', sans-serif !important;
            font-weight: 500 !important;
            text-transform: uppercase !important;
            letter-spacing: 0.05em !important;
            font-size: 12px !important;
            opacity: 0.8 !important;
        }
        .mc-calendar .mc-display__date {
            font-family: 'Space Grotesk', sans-serif !important;
            font-weight: 700 !important;
            font-size: 32px !important;
            margin-top: 4px !important;
        }
        .mc-calendar .mc-display__year {
            display: none !important; /* Hide redundant year header row */
        }
        .mc-calendar .mc-date--selected {
            background-color: var(--teal, #2F8F7E) !important;
            color: #FFFFFF !important;
            font-weight: 600 !important;
        }
        .mc-calendar .mc-date--today {
            border: 1.5px solid var(--teal, #2F8F7E) !important;
            color: var(--teal, #2F8F7E) !important;
            font-weight: 600 !important;
        }
        .mc-calendar .mc-date--today.mc-date--selected {
            color: #FFFFFF !important;
        }
        .mc-calendar .mc-btn__ok, 
        .mc-calendar .mc-btn__cancel,
        .mc-calendar .mc-btn__clear {
            color: var(--teal, #2F8F7E) !important;
            font-family: 'Space Grotesk', sans-serif !important;
            font-weight: 700 !important;
            font-size: 13.5px !important;
            letter-spacing: 0.02em !important;
            transition: background 0.15s !important;
            border-radius: 6px !important;
            padding: 6px 12px !important;
        }
        .mc-calendar .mc-btn__ok:hover, 
        .mc-calendar .mc-btn__cancel:hover,
        .mc-calendar .mc-btn__clear:hover {
            background-color: color-mix(in srgb, var(--teal, #2F8F7E) 8%, transparent) !important;
        }
        /* Style navigation controls */
        .mc-calendar .mc-month-year__month,
        .mc-calendar .mc-month-year__year {
            font-family: 'Space Grotesk', sans-serif !important;
            font-weight: 700 !important;
            color: var(--ink, #1B2A41) !important;
        }
        .mc-calendar .mc-month-year__arrow:hover {
            background-color: color-mix(in srgb, var(--teal, #2F8F7E) 8%, transparent) !important;
        }
        .mc-calendar .mc-month-year__arrow svg {
            fill: var(--ink, #1B2A41) !important;
        }
        .mc-calendar .mc-month-year__arrow:hover svg {
            fill: var(--teal, #2F8F7E) !important;
        }
        /* Style weekdays header row */
        .mc-calendar .mc-table__weekday {
            font-weight: 600 !important;
            color: var(--muted, #7C7568) !important;
            font-size: 12px !important;
        }
        /* Style standard days hover */
        .mc-calendar .mc-date:hover {
            background-color: color-mix(in srgb, var(--teal, #2F8F7E) 6%, transparent) !important;
        }
    </style>
    <style>
        #nprogress .bar { background: var(--teal, #2F8F7E); height: 3px; }
        #nprogress .peg  { box-shadow: 0 0 10px var(--teal, #2F8F7E), 0 0 5px var(--teal, #2F8F7E); }
        #nprogress .spinner-icon {
            border-top-color: var(--teal, #2F8F7E);
            border-left-color: var(--teal, #2F8F7E);
        }
        /* Button loading state */
        .btn-loading { opacity: 0.7; cursor: not-allowed; pointer-events: none; position: relative; }
        .btn-loading::after {
            content: '';
            display: inline-block;
            width: 11px; height: 11px;
            border: 2px solid transparent;
            border-top-color: currentColor;
            border-radius: 50%;
            animation: btn-spin .6s linear infinite;
            margin-left: 7px;
            vertical-align: middle;
        }
        @keyframes btn-spin { to { transform: rotate(360deg); } }

        /* ── Breadcrumb ──────────────────────────────────────── */
        .breadcrumb-nav {
            display: flex;
            align-items: center;
            gap: 6px;
            font-size: 12.5px;
            color: var(--muted);
            margin-bottom: 14px;
        }
        .breadcrumb-nav a {
            color: var(--teal);
            text-decoration: none;
            font-weight: 500;
        }
        .breadcrumb-nav a:hover { text-decoration: underline; }
        .breadcrumb-nav .sep { color: var(--line); font-size: 14px; }
        .breadcrumb-nav .current { color: var(--ink); font-weight: 600; }
    </style>
    @yield('styles')
</head>
<body>
<div class="app">

    <header class="top">
        <div class="brand">
            <span class="mark">RS</span>
            <div>
                <h1>Report Studio</h1>
                <div class="tagline">Dashboard laporan les — tinggal isi behavior, sisanya otomatis.</div>
            </div>
        </div>
        <div style="display: flex; gap: 8px; align-items: center;">
            <span style="font-size: 13px; color: var(--muted);">{{ auth()->user()->name }}</span>
            <form action="{{ route('logout') }}" method="POST" style="margin: 0;">
                @csrf
                <button type="submit" class="btn secondary" style="padding: 6px 12px; font-size: 12px;">Logout</button>
            </form>
        </div>
    </header>

    <nav class="tabs">
        <a href="{{ route('report.index') }}" class="{{ request()->routeIs('report.index') ? 'active' : '' }}">Buat Laporan</a>
        <a href="{{ route('students.index') }}" class="{{ request()->routeIs('students.index') ? 'active' : '' }}">Murid</a>
        <a href="{{ route('dataset.index') }}" class="{{ request()->routeIs('dataset.index') ? 'active' : '' }}">Dataset Gaya</a>
        <a href="{{ route('history.index') }}" class="{{ request()->routeIs('history.index') ? 'active' : '' }}">Riwayat</a>
        <a href="{{ route('settings.index') }}" class="{{ request()->routeIs('settings.index') ? 'active' : '' }}">Pengaturan</a>
    </nav>

    <!-- Alert Notifications -->
    @if(session('success'))
        <div class="alert alert-success">
            {{ session('success') }}
        </div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger">
            {{ session('error') }}
        </div>
    @endif
    @if($errors->any())
        <div class="alert alert-danger">
            <ul style="margin: 0; padding-left: 18px;">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <!-- Breadcrumb (optional, yielded by child views) -->
    @hasSection('breadcrumb')
    <nav class="breadcrumb-nav" aria-label="Breadcrumb">
        @yield('breadcrumb')
    </nav>
    @endif

    <!-- Main Content Panel -->
    <main>
        @yield('content')
    </main>

    <div class="footnote">Data tersimpan aman di server database Supabase. Versi web Laravel MVP.</div>
</div>

<!-- Global Toast Notification -->
<div class="toast" id="toast"></div>

<script src="https://cdn.jsdelivr.net/npm/nprogress@0.2.0/nprogress.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/tom-select@2.3.1/dist/js/tom-select.complete.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/mc-datepicker/dist/mc-calendar.min.js"></script>
<script>

    // ── Tom Select — auto-init all <select> elements ──────────
    document.addEventListener('DOMContentLoaded', () => {
        document.querySelectorAll('select').forEach(el => {
            // Skip if already initialized
            if (el.tomselect) return;

            const ts = new TomSelect(el, {
                create: false,
                allowEmptyOption: true,
                selectOnTab: true,
                onChange(value) {
                    // Re-fire native change so existing onchange= attributes still work
                    el.dispatchEvent(Object.assign(new Event('change', { bubbles: true }), { _fromTs: true }));
                }
            });
        });
    });

    // ── NProgress config ──────────────────────────────────────
    NProgress.configure({ showSpinner: false, speed: 300, minimum: 0.08 });

    // Start bar on any nav link click (not forms, not external)
    document.addEventListener('click', e => {
        const a = e.target.closest('a[href]');
        if (!a) return;
        const url = a.getAttribute('href');
        if (!url || url.startsWith('#') || url.startsWith('javascript') || a.target === '_blank') return;
        NProgress.start();
    });

    // Start bar on form submit + add loading state to submit button
    document.addEventListener('submit', e => {
        NProgress.start();
        const btn = e.target.querySelector('[type="submit"]');
        if (btn) {
            btn.classList.add('btn-loading');
            btn.disabled = true;
        }
    });

    // Stop bar when page finishes loading
    window.addEventListener('pageshow', () => NProgress.done());

    // ── Toast ─────────────────────────────────────────────────
    let toastTimer;
    function showToast(msg) {
        const el = document.getElementById('toast');
        el.textContent = msg;
        el.classList.add('show');
        clearTimeout(toastTimer);
        toastTimer = setTimeout(() => el.classList.remove('show'), 2200);
    }
</script>

@yield('scripts')
</body>
</html>
