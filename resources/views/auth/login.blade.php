<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login — Report Studio</title>
    <link rel="stylesheet" href="{{ asset('css/app-custom.css') }}">
    <style>
        body {
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            padding: 20px;
        }
        .login-container {
            width: 100%;
            max-width: 400px;
            animation: fade .22s ease;
        }
        .login-logo {
            text-align: center;
            margin-bottom: 24px;
        }
        .login-logo .mark {
            font-family: 'JetBrains Mono', monospace;
            font-weight: 700;
            background: var(--ink);
            color: var(--paper);
            padding: 4px 10px;
            border-radius: 6px;
            font-size: 16px;
            letter-spacing: 0.02em;
            display: inline-block;
            margin-bottom: 8px;
        }
        .login-logo h1 {
            font-size: 24px;
        }
        .checkbox-container {
            display: flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 16px;
            cursor: pointer;
        }
        .checkbox-container input {
            margin: 0;
            width: auto;
            cursor: pointer;
        }
        .checkbox-container label {
            margin: 0;
            font-size: 13px;
            color: var(--muted);
            cursor: pointer;
        }
    </style>
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
