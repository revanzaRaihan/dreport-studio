<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('page_title', __('Dashboard')) — Report Studio</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <link rel="icon" type="image/png" href="{{ asset('images/icons/favicon/favicon-96x96.png') }}" sizes="96x96" />
    <link rel="icon" type="image/svg+xml" href="{{ asset('images/icons/favicon/favicon.svg') }}" />
    <link rel="shortcut icon" href="{{ asset('images/icons/favicon/favicon.ico') }}" />
    <link rel="apple-touch-icon" sizes="180x180" href="{{ asset('images/icons/favicon/apple-touch-icon.png') }}" />
    <link rel="manifest" href="{{ asset('images/icons/favicon/site.webmanifest') }}" />

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
        /* When focused in single mode, hide the selected item so only the
           search input shows — same clean "type to search" UX as subject field */
        .ts-wrapper.single.focus .ts-control > .ts-item,
        .ts-wrapper.single.input-active .ts-control > .ts-item {
            display: none;
        }
        /* student-picker: always hide the item tag, always show the input —
           control_input.placeholder shows the selected name instead */
        .ts-wrapper.student-picker .ts-control .ts-item {
            display: none !important;
        }
        .ts-wrapper.student-picker .ts-control input {
            display: inline-block !important;
            width: auto !important;
            min-width: 4px;
            opacity: 1 !important;
        }
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

        /* Turbo navigation loading state overlay */
        body.turbo-loading {
            cursor: wait;
        }
        body.turbo-loading a,
        body.turbo-loading button {
            pointer-events: none !important;
        }

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



    <!-- JS Libraries -->
    <script src="https://cdn.jsdelivr.net/npm/nprogress@0.2.0/nprogress.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/tom-select@2.3.1/dist/js/tom-select.complete.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/mc-datepicker/dist/mc-calendar.min.js"></script>
    <script src="https://unpkg.com/lenis@1.1.13/dist/lenis.min.js"></script>

</head>
<body>
<div class="app container mx-auto px-4 md:px-6 max-w-5xl py-8">

    <header class="top">
        <div class="brand">
            <span class="mark">RS</span>
            <div>
                <h1>Report Studio</h1>
                <div class="tagline">{{ __('Dashboard laporan les — tinggal isi behavior, sisanya otomatis.') }}</div>
            </div>
        </div>
        <div style="display: flex; gap: 8px; align-items: center;">
            <span style="font-size: 13px; color: var(--muted);">{{ auth()->user()->name }}</span>
            <form action="{{ route('logout') }}" method="POST" style="margin: 0;">
                @csrf
                <button type="submit" class="btn secondary" style="padding: 6px 12px; font-size: 12px;">{{ __('Logout') }}</button>
            </form>
        </div>
    </header>

    <nav class="tabs">
        <a href="{{ route('report.index') }}" class="{{ request()->routeIs('report.index') ? 'active' : '' }}">
            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 20h9"/><path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4L16.5 3.5z"/></svg>
            {{ __('Buat Laporan') }}
        </a>
        <a href="{{ route('students.index') }}" class="{{ request()->routeIs('students.index') ? 'active' : '' }}">
            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
            {{ __('Murid') }}
        </a>
        <a href="{{ route('schedule.index') }}" class="{{ request()->routeIs('schedule.*') ? 'active' : '' }}">
            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
            {{ __('Jadwal') }}
        </a>
        <a href="{{ route('pending-reports.index') }}" class="{{ request()->routeIs('pending-reports.*') ? 'active' : '' }}">
            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="8" y1="6" x2="21" y2="6"/><line x1="8" y1="12" x2="21" y2="12"/><line x1="8" y1="18" x2="21" y2="18"/><line x1="3" y1="6" x2="3.01" y2="6"/><line x1="3" y1="12" x2="3.01" y2="12"/><line x1="3" y1="18" x2="3.01" y2="18"/></svg>
            {{ __('Listing Report') }}
        </a>
        <a href="{{ route('dataset.index') }}" class="{{ request()->routeIs('dataset.index') ? 'active' : '' }}">
            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 2L2 7l10 5 10-5-10-5z"/><path d="M2 17l10 5 10-5"/><path d="M2 12l10 5 10-5"/></svg>
            {{ __('Dataset Gaya') }}
        </a>
        <a href="{{ route('history.index') }}" class="{{ request()->routeIs('history.index') ? 'active' : '' }}">
            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
            {{ __('Riwayat') }}
        </a>
        <a href="{{ route('settings.index') }}" class="{{ request()->routeIs('settings.index') ? 'active' : '' }}">
            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 1 1-2.83 2.83l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-4 0v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 1 1-2.83-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1 0-4h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 1 1 2.83-2.83l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 4 0v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 1 1 2.83 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 0 4h-.09a1.65 1.65 0 0 0-1.51 1z"/></svg>
            {{ __('Pengaturan') }}
        </a>
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

    @hasSection('page_title')
    <div class="page-header" style="margin-bottom: 22px; border-bottom: 1.5px solid var(--line); padding-bottom: 12px; margin-top: 6px;">
        <h2 style="font-family: 'Space Grotesk', sans-serif; font-size: 22px; font-weight: 700; color: var(--ink); margin: 0 0 6px; letter-spacing: -0.01em;">
            @yield('page_title')
        </h2>
        @hasSection('page_description')
            <p style="color: var(--muted); font-size: 13px; margin: 0; line-height: 1.5;">
                @yield('page_description')
            </p>
        @endif
    </div>
    @endif

    <!-- Main Content Panel -->
    <main>
        @yield('content')
    </main>

    <div class="footnote">{{ __('Data tersimpan aman di server database Supabase. Versi web Laravel MVP.') }}</div>
</div>

<!-- Global Toast Notification -->
<div class="toast" id="toast"></div>

<!-- Universal Delete Modal -->
<div class="modal-backdrop" id="deleteModal" style="z-index: 200;">
    <div class="modal-content" style="max-width: 400px;">
        <h2 style="font-family: 'Space Grotesk', sans-serif; font-size: 16px; font-weight: 600; margin-bottom: 8px;">{{ __('Konfirmasi Hapus') }}</h2>
        <p class="desc" id="deleteModalMessage" style="margin-bottom: 20px; font-size: 14px; color: var(--muted);"></p>
        
        <form id="deleteModalForm" method="POST" style="margin: 0;">
            @csrf
            @method('DELETE')
            <div class="actions-row" style="justify-content: flex-end; gap: 8px;">
                <button type="button" class="btn secondary" onclick="closeDeleteModal()">{{ __('Batal') }}</button>
                <button type="submit" class="btn danger">{{ __('Hapus') }}</button>
            </div>
        </form>
    </div>
</div>

@yield('scripts')
</body>
</html>
