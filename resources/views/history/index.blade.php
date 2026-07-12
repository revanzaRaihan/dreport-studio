@extends('layouts.app')

@section('page_title', 'Riwayat Laporan')
@section('page_description', 'Cari, filter, edit, dan bagikan laporan belajar yang telah berhasil disimpan.')

@section('content')
<section class="panel active" id="tab-riwayat">
    <div class="card">
        <div class="history-header" style="display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 16px; margin-bottom: 20px; border-bottom: 1px solid var(--line); padding-bottom: 16px;">
            <div style="flex: 1; min-width: 250px;">
                <h2 style="margin-bottom: 2px; font-size: 16px;">Riwayat Laporan</h2>
                <p class="desc" style="margin: 0; font-size: 12.5px;">Daftar murid yang memiliki riwayat laporan belajar les privat.</p>
            </div>

            <form method="GET" action="{{ route('history.index') }}" class="search-bar" style="margin: 0; max-width: 320px;">
                <input type="text" name="search" value="{{ $search ?? '' }}" placeholder="Cari nama murid atau subjek..." autocomplete="off">
                <button type="submit" class="btn-icon" title="Cari">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/>
                    </svg>
                </button>
                @if($search)
                    <a href="{{ route('history.index') }}" class="btn-icon" title="Hapus filter">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/>
                        </svg>
                    </a>
                @endif
            </form>
        </div>

        @if($studentsWithReports->isEmpty())
            <div class="empty">
                @if($search)
                    Tidak ada murid dengan laporan yang cocok dengan "{{ $search }}".
                @else
                    Belum ada riwayat laporan tersimpan.
                @endif
            </div>
        @else
            @foreach($studentsWithReports as $student)
                <div class="list-item">
                    <div class="meta">
                        <strong>{{ $student->name }}</strong>
                        <span>{{ $student->subject }} · memiliki {{ $student->reports_count }} riwayat laporan</span>
                    </div>
                    <div style="display: flex; gap: 6px; align-items: center; flex-shrink: 0;">
                        <a href="{{ route('history.student', $student->id) }}" class="btn" style="padding: 6px 12px; font-size: 12.5px; text-decoration: none;">Lihat Detail</a>
                    </div>
                </div>
            @endforeach

            <x-pagination :paginator="$studentsWithReports" />
        @endif
    </div>
</section>
@endsection
