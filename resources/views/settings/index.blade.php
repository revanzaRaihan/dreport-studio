@extends('layouts.app')

@section('page_title', __('Pengaturan Aplikasi'))
@section('page_description', __('Konfigurasi kunci API kecerdasan buatan (AI) dan nomor tujuan WhatsApp.'))

@section('content')
<section class="panel active" id="tab-pengaturan">
    <div class="card">
        <h2>{{ __('Konfigurasi AI Provider') }}</h2>
        <p class="desc">{{ __('API key dan nama model Anda disimpan dengan aman di server backend (Supabase), tidak terekspos ke sisi klien/browser.') }}</p>
        
        <form action="{{ route('settings.update') }}" method="POST">
            @csrf
            @method('PUT')

            <label for="aiProvider">AI Provider</label>
            <select id="aiProvider" name="ai_provider">
                <option value="gemini" {{ $provider === 'gemini' ? 'selected' : '' }}>Google Gemini</option>
                <option value="groq" {{ $provider === 'groq' ? 'selected' : '' }}>Groq Cloud API</option>
            </select>

            <label for="apiKey">API Key</label>
            <input type="password" id="apiKey" name="ai_api_key" value="{{ $maskedKey }}" placeholder="{{ __('Masukkan API Key baru atau biarkan seperti ini untuk tetap menyimpan yang lama') }}">
            <span style="display: block; font-size: 11.5px; color: var(--muted); margin-top: -10px; margin-bottom: 14px;">
                {{ __('Catatan: API key disembunyikan demi keamanan. Tulis ulang jika Anda ingin memperbaruinya.') }}
            </span>

            <label for="modelName">{{ __('Nama Model') }}</label>
            <input type="text" id="modelName" name="ai_model" value="{{ $model }}" placeholder="gemini-2.5-flash" required>
            <p class="desc" id="modelHelpText" style="margin-top:-6px; font-size: 11.5px; margin-bottom: 14px;">
                {{ __('Rekomendasi model Gemini: <code>gemini-2.5-flash</code> atau <code>gemini-1.5-flash</code>.') }}
            </p>
            
            <label for="adminWaNumber">{{ __('Nomor WhatsApp Admin') }}</label>
            <input type="text" id="adminWaNumber" name="admin_wa_number" value="{{ $waNumber }}" placeholder="mis. 628123456789" autocomplete="off">
            <p class="desc" style="margin-top:-6px; font-size: 11.5px; margin-bottom: 20px;">
                {!! __('Gunakan format kode negara tanpa karakter \'+\' atau spasi (mis. <code>628123456789</code>). Jika diisi, tombol Kirim WA akan langsung tertuju ke nomor ini.') !!}
            </p>

            <label for="appLocale">{{ __('Bahasa Aplikasi') }}</label>
            <select id="appLocale" name="app_locale">
                <option value="id" {{ $locale === 'id' ? 'selected' : '' }}>Bahasa Indonesia</option>
                <option value="en" {{ $locale === 'en' ? 'selected' : '' }}>English (Inggris)</option>
            </select>
            <p class="desc" style="margin-top:-6px; font-size: 11.5px; margin-bottom: 20px;">
                {{ __('Pilih bahasa antarmuka aplikasi.') }}
            </p>

            <button type="submit" class="btn">{{ __('Simpan Pengaturan') }}</button>
        </form>
    </div>
    
    <div class="card">
        <h2>{{ __('Informasi Deployment') }}</h2>
        <p class="desc">{{ __('Detail deployment hosting Report Studio.') }}</p>
        <div style="font-size: 12.5px; line-height: 1.6; color: var(--ink);">
            <strong>Database:</strong> PostgreSQL Supabase<br>
            <strong>Engine:</strong> Laravel Framework 13.x<br>
            <strong>Target Hosting:</strong> InfinityFree / Standard Shared Hosting Portable
        </div>
    </div>
</section>
@endsection


