<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login — Report Studio</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <link rel="icon" type="image/png" href="{{ asset('images/icons/favicon/favicon-96x96.png') }}" sizes="96x96" />
    <link rel="icon" type="image/svg+xml" href="{{ asset('images/icons/favicon/favicon.svg') }}" />
    <link rel="shortcut icon" href="{{ asset('images/icons/favicon/favicon.ico') }}" />
    <link rel="apple-touch-icon" sizes="180x180" href="{{ asset('images/icons/favicon/apple-touch-icon.png') }}" />
    <link rel="manifest" href="{{ asset('images/icons/favicon/site.webmanifest') }}" />
</head>
<body>
<div class="login-container">
    <div class="login-logo">
        <span class="mark">RS</span>
        <h1>Report Studio</h1>
        <div class="tagline">Silakan masuk untuk mengelola laporan progres murid.</div>
    </div>

    @if(session('status'))
        <div class="alert alert-success">
            {{ session('status') }}
        </div>
    @endif
    @if($errors->any())
        <div class="alert alert-danger">
            <ul style="margin: 0; padding-left: 18px; font-size: 12.5px;">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="card">
        <form action="{{ route('login') }}" method="POST">
            @csrf
            
            <label for="email">Alamat Email</label>
            <input type="email" id="email" name="email" value="{{ old('email') }}" required autofocus placeholder="nama@email.com">

            <label for="password">Kata Sandi</label>
            <input type="password" id="password" name="password" required placeholder="••••••••">

            <div class="checkbox-container">
                <input type="checkbox" id="remember_me" name="remember">
                <label for="remember_me">Ingat saya di perangkat ini</label>
            </div>

            <button type="submit" class="btn" style="width: 100%;">Masuk</button>
        </form>
    </div>
    
    <div class="footnote">Sistem Keamanan Terpusat · Powered by Laravel & Supabase</div>
</div>
</body>
</html>
