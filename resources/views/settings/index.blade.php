@extends('layouts.app')

@section('content')
<section class="panel active" id="tab-pengaturan">
    <div class="card">
        <h2>Konfigurasi AI Provider</h2>
        <p class="desc">API key dan nama model Anda disimpan dengan aman di server backend (Supabase), tidak terekspos ke sisi klien/browser.</p>
        
        <form action="{{ route('settings.update') }}" method="POST">
            @csrf
            @method('PUT')

            <label for="aiProvider">AI Provider</label>
            <select id="aiProvider" name="ai_provider">
                <option value="gemini" {{ $provider === 'gemini' ? 'selected' : '' }}>Google Gemini</option>
                <option value="groq" {{ $provider === 'groq' ? 'selected' : '' }}>Groq Cloud API</option>
            </select>

            <label for="apiKey">API Key</label>
            <input type="password" id="apiKey" name="ai_api_key" value="{{ $maskedKey }}" placeholder="Masukkan API Key baru atau biarkan seperti ini untuk tetap menyimpan yang lama">
            <span style="display: block; font-size: 11.5px; color: var(--muted); margin-top: -10px; margin-bottom: 14px;">
                Catatan: API key disembunyikan demi keamanan. Tulis ulang jika Anda ingin memperbaruinya.
            </span>

            <label for="modelName">Nama Model</label>
            <input type="text" id="modelName" name="ai_model" value="{{ $model }}" placeholder="gemini-2.5-flash" required>
            
            <p class="desc" id="modelHelpText" style="margin-top:-6px; font-size: 11.5px;">
                Rekomendasi model Gemini: <code>gemini-2.5-flash</code> atau <code>gemini-1.5-flash</code>.
            </p>

            <button type="submit" class="btn">Simpan Pengaturan</button>
        </form>
    </div>
    
    <div class="card">
        <h2>Informasi Deployment</h2>
        <p class="desc">Detail deployment hosting Report Studio.</p>
        <div style="font-size: 12.5px; line-height: 1.6; color: var(--ink);">
            <strong>Database:</strong> PostgreSQL Supabase<br>
            <strong>Engine:</strong> Laravel Framework 13.x<br>
            <strong>Target Hosting:</strong> InfinityFree / Standard Shared Hosting Portable
        </div>
    </div>
</section>
@endsection

@section('scripts')
<script>
    function updateModelPlaceholder(provider) {
        const modelInput = document.getElementById('modelName');
        const helpText = document.getElementById('modelHelpText');
        
        if (provider === 'groq') {
            modelInput.placeholder = 'llama3-8b-8192';
            if (modelInput.value === 'gemini-2.5-flash' || modelInput.value === 'gemini-1.5-flash') {
                modelInput.value = 'llama3-8b-8192';
            }
            helpText.innerHTML = 'Rekomendasi model Groq: <code>llama3-8b-8192</code> atau <code>mixtral-8x7b-32768</code>.';
        } else {
            modelInput.placeholder = 'gemini-2.5-flash';
            if (modelInput.value === 'llama3-8b-8192' || modelInput.value === 'mixtral-8x7b-32768') {
                modelInput.value = 'gemini-2.5-flash';
            }
            helpText.innerHTML = 'Rekomendasi model Gemini: <code>gemini-2.5-flash</code> atau <code>gemini-1.5-flash</code>.';
        }
    }

    // Wire Tom Select onChange + run once on load to sync UI with saved setting
    document.addEventListener('DOMContentLoaded', () => {
        const el = document.getElementById('aiProvider');
        if (!el) return;
        updateModelPlaceholder(el.value);               // initial sync
        requestAnimationFrame(() => {
            if (el.tomselect) {
                el.tomselect.on('change', value => updateModelPlaceholder(value));
            }
        });
    });
</script>
@endsection
