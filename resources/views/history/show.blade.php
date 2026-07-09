@extends('layouts.app')

@section('breadcrumb')
    <a href="{{ route('history.index') }}">Riwayat</a>
    <span class="sep">›</span>
    <a href="{{ route('students.index') }}">Murid</a>
    <span class="sep">›</span>
    <span class="current">{{ $student->name }}</span>
@endsection

@section('content')
<section class="panel active">
    <div class="card">
        {{-- Header --}}
        <div style="display: flex; align-items: flex-start; justify-content: space-between; flex-wrap: wrap; gap: 10px; margin-bottom: 12px;">
            <div>
                <h2 style="margin-bottom: 4px;">{{ $student->name }}</h2>
                <p class="desc" style="margin: 0;">
                    {{ $student->subject }} · {{ $reports->total() }} laporan tersimpan · sudah {{ $student->meeting_count }} meeting
                </p>
            </div>
            <a href="{{ route('history.index') }}" class="btn secondary" style="padding: 6px 12px; font-size: 12px; text-decoration: none; flex-shrink: 0;">← Semua riwayat</a>
        </div>

        {{-- Search bar --}}
        <form method="GET" action="{{ route('history.student', $student->id) }}" class="search-bar">
            <input type="text" name="search" value="{{ $search ?? '' }}" placeholder="Cari materi, behavior, atau isi laporan...">
            <button type="submit" class="btn-icon" title="Cari">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <polygon points="22 3 2 3 10 12.46 10 19 14 21 14 12.46 22 3"/>
                </svg>
            </button>
            @if($search)
                <a href="{{ route('history.student', $student->id) }}" class="btn-icon" title="Hapus filter">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/>
                    </svg>
                </a>
            @endif
        </form>

        <hr style="border: none; border-top: 1px solid var(--line); margin: 4px 0 14px;">

        @if($reports->isEmpty())
            <div class="empty">Belum ada laporan tersimpan untuk {{ $student->name }}.</div>
        @else
            @foreach($reports as $report)
                <div class="list-item" style="align-items: flex-start; padding: 16px 4px;">
                    <div class="meta" style="max-width: 80%;">
                        <strong>
                            Meeting ke-{{ $report->meeting_number }}
                            <span class="badge amber">M{{ $report->meeting_number }}</span>
                        </strong>
                        <span style="font-size: 11.5px; margin-top: 2px;">
                            {{ $report->report_date->format('d/m/Y') }}
                        </span>

                        <div style="font-size: 11px; color: var(--muted); background: #FAF9F6; padding: 6px 8px; border-radius: 4px; margin-top: 6px; border: 1px solid #EAE5DB;">
                            <strong>Materi:</strong> {{ $report->materi }} <br>
                            <strong>Behavior:</strong> {{ $report->behavior }}
                        </div>

                        <span style="color: var(--ink); white-space: pre-wrap; margin-top: 10px; display: block; font-size: 13.5px; line-height: 1.6;">{{ $report->content }}</span>
                    </div>
                    <div style="display: flex; flex-direction: column; gap: 6px; flex-shrink: 0; align-items: flex-end;">
                        <button class="btn secondary" style="padding: 6px 10px;" onclick="copyText('{{ addslashes($report->content) }}')">Copy</button>

                        <form action="{{ route('history.destroy', $report->id) }}" method="POST"
                              onsubmit="return confirm('Hapus laporan ini dari riwayat?')" style="margin: 0;">
                            @csrf
                            @method('DELETE')
                            <input type="hidden" name="_redirect" value="{{ route('history.student', $student->id) }}">
                            <button type="submit" class="btn danger" style="padding: 6px 10px; font-size: 12px;">Hapus</button>
                        </form>
                    </div>
                </div>
            @endforeach

            <x-pagination :paginator="$reports" />
        @endif
    </div>
</section>
@endsection

@section('scripts')
<script>
    function copyText(text) {
        navigator.clipboard.writeText(text)
            .then(() => showToast('Disalin ke clipboard'))
            .catch(() => showToast('Gagal menyalin. Silakan copy manual.'));
    }
</script>
@endsection
