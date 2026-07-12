@extends('layouts.app')

@section('page_title', 'Dataset Gaya Penulisan')
@section('page_description', 'Latih kecerdasan buatan dengan contoh laporan lama agar hasil generate mirip gaya tulisan Anda.')

@section('content')
<section class="panel active" id="tab-dataset">
    <!-- Tambah Dataset Card -->
    <div class="card">
        <h2>Dataset gaya penulisan</h2>
        <p class="desc">Paste laporan-laporan lama kamu di sini. Semakin banyak &amp; bervariasi contohnya, semakin mirip hasil generate-nya sama gaya nulis kamu. Dataset ini dijadikan referensi tiap kali generate laporan baru.</p>
        
        <form action="{{ route('dataset.store') }}" method="POST">
            @csrf
            
            <div style="margin-bottom: 14px; display: flex; gap: 20px; align-items: center; flex-wrap: wrap;">
                <span style="font-size: 13.5px; font-weight: 600; color: var(--ink);">Bahasa Contoh Laporan:</span>
                <label style="display: inline-flex; align-items: center; gap: 6px; font-size: 13.5px; cursor: pointer; font-weight: normal; margin: 0; color: var(--ink);">
                    <input type="radio" name="language" value="id" checked style="margin: 0; width: 16px; height: 16px; accent-color: var(--teal);">
                    Bahasa Indonesia
                </label>
                <label style="display: inline-flex; align-items: center; gap: 6px; font-size: 13.5px; cursor: pointer; font-weight: normal; margin: 0; color: var(--ink);">
                    <input type="radio" name="language" value="en" style="margin: 0; width: 16px; height: 16px; accent-color: var(--teal);">
                    English (Bahasa Inggris)
                </label>
            </div>

            <textarea name="body" style="min-height: 120px;" placeholder="03/07/2026&#10;Javascript Developer Meeting 9, pada pertemuan kali ini Renziro melanjutkan..." required></textarea>
            <button type="submit" class="btn">Tambah ke dataset</button>
        </form>
    </div>

    <!-- Contoh Tersimpan Card -->
    <div class="card">
        <div style="display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid var(--line); padding-bottom: 12px; margin-bottom: 16px; flex-wrap: wrap; gap: 12px;">
            <h2 style="margin: 0;">Contoh tersimpan (<span id="datasetCount">{{ $dataset->count() }}</span>)</h2>
            <div class="sub-tabs" style="display: flex; gap: 4px; background: #FAF9F6; border: 1.5px solid var(--line); padding: 4px; border-radius: 8px;">
                <button class="sub-tab-btn active" onclick="filterLanguage('all')" style="padding: 4px 10px; font-size: 12px; font-weight: 600; border: none; border-radius: 6px; cursor: pointer; background: var(--teal); color: white; transition: all 0.2s;">Semua</button>
                <button class="sub-tab-btn" onclick="filterLanguage('id')" style="padding: 4px 10px; font-size: 12px; font-weight: 600; border: none; border-radius: 6px; cursor: pointer; background: transparent; color: var(--muted); transition: all 0.2s;">Bahasa Indonesia ({{ $dataset->where('language', 'id')->count() }})</button>
                <button class="sub-tab-btn" onclick="filterLanguage('en')" style="padding: 4px 10px; font-size: 12px; font-weight: 600; border: none; border-radius: 6px; cursor: pointer; background: transparent; color: var(--muted); transition: all 0.2s;">English ({{ $dataset->where('language', 'en')->count() }})</button>
            </div>
        </div>
        
        @if($dataset->isEmpty())
            <div class="empty">Belum ada contoh laporan tersimpan.</div>
        @else
            @foreach($dataset as $entry)
                <div class="list-item dataset-item lang-{{ $entry->language }}" style="align-items: flex-start;">
                    <div class="meta" style="max-width: 80%;">
                        <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 6px;">
                            <span class="badge" style="background: {{ $entry->language === 'en' ? '#EBF5FF' : '#E6FFFA' }}; color: {{ $entry->language === 'en' ? '#1E40AF' : '#047857' }}; border: none; font-size: 10.5px; padding: 2px 8px; border-radius: 4px; font-weight: 600;">
                                {{ $entry->language === 'en' ? 'English' : 'Bahasa Indonesia' }}
                            </span>
                        </div>
                        <span style="font-size: 13.5px; color: var(--ink); white-space: pre-wrap; line-height: 1.5;">{{ Str::limit($entry->body, 160, '…') }}</span>
                    </div>
                    <form action="{{ route('dataset.destroy', $entry->id) }}" method="POST" onsubmit="return confirm('Hapus contoh laporan ini dari dataset?')" style="margin: 0; flex-shrink: 0;">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn danger" style="padding: 6px 10px;">Hapus</button>
                    </form>
                </div>
            @endforeach
        @endif
    </div>
</section>
@endsection

@section('scripts')
<script>
    function filterLanguage(lang) {
        // Toggle active button style
        const buttons = document.querySelectorAll('.sub-tab-btn');
        buttons.forEach(btn => {
            if (btn.getAttribute('onclick').includes(`'${lang}'`)) {
                btn.style.background = 'var(--teal)';
                btn.style.color = 'white';
                btn.classList.add('active');
            } else {
                btn.style.background = 'transparent';
                btn.style.color = 'var(--muted)';
                btn.classList.remove('active');
            }
        });

        // Filter list items
        const items = document.querySelectorAll('.dataset-item');
        let visibleCount = 0;
        items.forEach(item => {
            if (lang === 'all' || item.classList.contains(`lang-${lang}`)) {
                item.style.display = 'flex';
                visibleCount++;
            } else {
                item.style.display = 'none';
            }
        });

        // Handle empty message
        let tempEmpty = document.querySelector('.temp-empty');
        if (visibleCount === 0) {
            if (!tempEmpty) {
                const empty = document.createElement('div');
                empty.className = 'empty temp-empty';
                empty.textContent = 'Tidak ada contoh laporan untuk kategori ini.';
                document.querySelector('.card:last-of-type').appendChild(empty);
            }
            const defaultEmpty = document.querySelector('.empty:not(.temp-empty)');
            if (defaultEmpty) defaultEmpty.style.display = 'none';
        } else {
            if (tempEmpty) {
                tempEmpty.remove();
            }
            const defaultEmpty = document.querySelector('.empty:not(.temp-empty)');
            if (defaultEmpty) defaultEmpty.style.display = 'block';
        }
    }
</script>
@endsection
