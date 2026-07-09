@extends('layouts.app')

@section('content')
<section class="panel active" id="tab-dataset">
    <!-- Tambah Dataset Card -->
    <div class="card">
        <h2>Dataset gaya penulisan</h2>
        <p class="desc">Paste laporan-laporan lama kamu di sini. Semakin banyak &amp; bervariasi contohnya, semakin mirip hasil generate-nya sama gaya nulis kamu. Dataset ini dijadikan referensi tiap kali generate laporan baru.</p>
        
        <form action="{{ route('dataset.store') }}" method="POST">
            @csrf
            <textarea name="body" style="min-height: 120px;" placeholder="03/07/2026&#10;Javascript Developer Meeting 9, pada pertemuan kali ini Renziro melanjutkan..." required></textarea>
            <button type="submit" class="btn">Tambah ke dataset</button>
        </form>
    </div>

    <!-- Contoh Tersimpan Card -->
    <div class="card">
        <h2>Contoh tersimpan (<span id="datasetCount">{{ $dataset->count() }}</span>)</h2>
        
        @if($dataset->isEmpty())
            <div class="empty">Belum ada contoh laporan tersimpan.</div>
        @else
            @foreach($dataset as $entry)
                <div class="list-item" style="align-items: flex-start;">
                    <div class="meta" style="max-width: 80%;">
                        <span style="font-size: 13px; color: var(--ink); white-space: pre-wrap;">{{ Str::limit($entry->body, 160, '…') }}</span>
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
