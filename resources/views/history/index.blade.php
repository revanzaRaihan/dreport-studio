@extends('layouts.app')

@section('styles')
<style>
    select.no-tom-select:focus {
        border-color: var(--teal) !important;
        box-shadow: 0 0 0 3px color-mix(in srgb, var(--teal) 15%, transparent) !important;
        outline: none;
    }
</style>
@endsection

@section('content')
<section class="panel active" id="tab-riwayat">
    <div class="card">
        {{-- Header --}}
        {{-- Header & Filters --}}
        <div class="history-header" style="display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 16px; margin-bottom: 20px; border-bottom: 1px solid var(--line); padding-bottom: 16px;">
            <div style="flex: 1; min-width: 250px;">
                <h2 style="margin-bottom: 2px; font-size: 16px;">
                    @if($student) Riwayat — {{ $student->name }} @else Riwayat laporan @endif
                </h2>
                <p class="desc" style="margin: 0; font-size: 12.5px;">
                    @if($student) {{ $student->subject }} · {{ $reports->total() }} laporan tersimpan
                    @else Semua laporan yang sudah kamu simpan. @endif
                </p>
            </div>

            <form method="GET" action="{{ route('history.index') }}" id="historyFilterForm" style="display: flex; align-items: center; gap: 8px; margin: 0; flex-wrap: wrap; justify-content: flex-end; flex: 2; min-width: 300px;">
                {{-- Student filter dropdown --}}
                @if($students->isNotEmpty())
                    <div style="flex-shrink: 0;">
                        <select name="student_id" id="studentFilter" class="no-tom-select" onchange="document.getElementById('historyFilterForm').submit()" style="margin-bottom: 0; padding: 8px 32px 8px 12px; height: 38px; background-color: #FCFAF6; border: 1.5px solid var(--line); border-radius: 8px; font-size: 13px; color: var(--ink); appearance: none; background-image: url(&quot;data:image/svg+xml;charset=UTF-8,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 12 12'%3E%3Cpath fill='%237C7568' d='M6 8L1 3h10z'/%3E%3C/svg%3E&quot;); background-repeat: no-repeat; background-position: right 12px center; background-size: 10px; cursor: pointer; min-width: 155px; outline: none; transition: border-color 0.15s;">
                            <option value="">Semua murid</option>
                            @foreach($students as $s)
                                <option value="{{ $s->id }}" {{ $student && $student->id === $s->id ? 'selected' : '' }}>{{ $s->name }}</option>
                            @endforeach
                        </select>
                    </div>
                @endif

                {{-- Search text --}}
                <div style="position: relative; display: flex; align-items: center; flex-shrink: 0;">
                    <input type="text" name="search" value="{{ $search ?? '' }}" placeholder="Cari laporan..." style="margin-bottom: 0; padding: 8px 32px 8px 12px; font-size: 13px; width: 160px; height: 38px; border: 1.5px solid var(--line); border-radius: 8px;">
                    <button type="submit" style="position: absolute; right: 8px; background: none; border: none; padding: 4px; cursor: pointer; color: var(--muted); display: flex; align-items: center; justify-content: center;" title="Cari">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/>
                        </svg>
                    </button>
                </div>

                @if($search || $student)
                    <a href="{{ route('history.index') }}" class="btn secondary" style="padding: 8px 12px; font-size: 12.5px; text-decoration: none; flex-shrink: 0; white-space: nowrap; height: 38px; display: inline-flex; align-items: center; justify-content: center;">Hapus filter</a>
                @endif
            </form>
        </div>

        @if($reports->isEmpty())
            <div class="empty">
                @if($search || $student) Tidak ada laporan yang cocok dengan filter ini.
                @else Belum ada laporan tersimpan. @endif
            </div>
        @else
            @foreach($reports as $report)
                <div class="list-item" style="align-items: flex-start; padding: 16px 4px;">
                    <div class="meta" style="max-width: 80%;">
                        <strong>
                            {{ $report->student_name }} 
                            <span class="badge amber">M{{ $report->meeting_number }}</span>
                        </strong>
                        <span style="font-size: 11.5px; margin-top: 2px;">
                            {{ $report->report_date->format('d/m/Y') }} · {{ $report->subject }}
                        </span>
                        <div style="font-size: 11px; color: var(--muted); background: #FAF9F6; padding: 6px 8px; border-radius: 4px; margin-top: 6px; border: 1px solid #EAE5DB;">
                            <strong>Materi:</strong> {{ $report->materi }} <br>
                            <strong>Behavior:</strong> {{ $report->behavior }}
                        </div>
                        <span style="color: var(--ink); white-space: pre-wrap; margin-top: 10px; display: block; font-size: 13.5px; line-height: 1.6;">{{ $report->content }}</span>
                    </div>
                    <div style="display: flex; flex-direction: column; gap: 6px; flex-shrink: 0; align-items: flex-end;">
                        <button class="btn secondary" style="padding: 6px 10px;" onclick="copyHistoryText('{{ addslashes($report->content) }}')">Copy</button>
                        <form action="{{ route('history.destroy', $report->id) }}" method="POST" onsubmit="return confirm('Hapus laporan ini dari riwayat?')" style="margin: 0;">
                            @csrf
                            @method('DELETE')
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
    function copyHistoryText(text) {
        navigator.clipboard.writeText(text)
            .then(() => showToast('Disalin ke clipboard'))
            .catch(() => showToast('Gagal menyalin. Silakan copy manual.'));
    }
</script>
@endsection
