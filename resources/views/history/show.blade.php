@extends('layouts.app')

@section('page_title', 'Riwayat Laporan: ' . $student->name)
@section('page_description', 'Daftar seluruh laporan belajar yang telah tersimpan untuk murid ini.')

@section('breadcrumb')
    <a href="{{ route('history.index') }}">Riwayat</a>
    <span class="sep">›</span>
    <a href="{{ route('students.index') }}">Murid</a>
    <span class="sep">›</span>
    <span class="current">{{ $student->name }}</span>
@endsection

@section('content')
<section class="panel active" id="tab-riwayat-murid" data-admin-wa-number="{{ $adminWaNumber }}">
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
                        
                        @if($report->image_url)
                            <div style="margin-top: 12px; max-width: 100%;">
                                <img src="{{ $report->image_url }}" alt="Dokumentasi Kelas" style="max-width: 260px; max-height: 180px; object-fit: cover; border-radius: 8px; border: 1.5px solid var(--line, #E4DCCE); box-shadow: 0 2px 8px rgba(0,0,0,0.04);">
                            </div>
                        @endif
                    </div>
                    <div style="display: flex; flex-direction: column; gap: 6px; flex-shrink: 0; align-items: flex-end;">
                        <button class="btn btn-wa" style="padding: 6px 10px; background-color: #25D366; color: white; border: none;"
                                data-meeting="{{ $report->meeting_number }}"
                                data-date="{{ $report->report_date->format('d/m/Y') }}"
                                data-materi="{{ $report->materi }}"
                                data-behavior="{{ $report->behavior }}"
                                data-content="{{ $report->content }}"
                                data-image="{{ $report->image_url }}">
                            Kirim WA
                        </button>

                        <button class="btn secondary" style="padding: 6px 10px;" onclick="copyText('{{ addslashes($report->content) }}')">Copy</button>
                        
                        <button class="btn secondary btn-edit" style="padding: 6px 10px;"
                                data-id="{{ $report->id }}"
                                data-meeting="{{ $report->meeting_number }}"
                                data-date="{{ $report->report_date->format('Y-m-d') }}"
                                data-materi="{{ $report->materi }}"
                                data-behavior="{{ $report->behavior }}"
                                data-content="{{ $report->content }}"
                                data-image="{{ $report->image_url }}">
                            Edit
                        </button>

                        <button type="button" class="btn danger" style="padding: 6px 10px; font-size: 12px;" onclick="openDeleteModal('{{ route('history.destroy', $report->id) }}', 'Apakah Anda yakin ingin menghapus laporan ini dari riwayat?')">Hapus</button>
                    </div>
                </div>
            @endforeach

            <x-pagination :paginator="$reports" />
        @endif
    </div>
</section>

<!-- Edit Report Modal -->
<div class="modal-backdrop" id="editReportModal">
    <div class="modal-content" style="max-width: 600px; width: 90%;">
        <h2 style="font-family: 'Space Grotesk', sans-serif; font-size: 16px; font-weight: 600; margin-bottom: 8px;">Edit Laporan Belajar</h2>
        <p class="desc" style="margin-bottom: 16px;">Ubah rincian pertemuan, materi, behavior, isi laporan, atau unggah foto baru.</p>
        
        <form id="editReportForm" method="POST" enctype="multipart/form-data">
            @csrf
            @method('PUT')
            
            <div class="row" style="margin-bottom: 12px;">
                <div>
                    <label for="editMeetingNumber">Pertemuan Ke-</label>
                    <input type="number" id="editMeetingNumber" name="meeting_number" min="1" required>
                </div>
                <div>
                    <label for="editReportDate">Tanggal Pertemuan</label>
                    <input type="date" id="editReportDate" name="report_date" required>
                </div>
            </div>

            <label for="editMateri">Materi</label>
            <input type="text" id="editMateri" name="materi" required autocomplete="off">

            <label for="editBehavior">Behavior Murid</label>
            <textarea id="editBehavior" name="behavior" rows="3" required style="width: 100%; border: 1.5px solid var(--line); border-radius: 8px; padding: 10px 12px; font-family: inherit; font-size: 14px; margin-bottom: 12px; box-sizing: border-box; resize: vertical;"></textarea>

            <label for="editContent">Isi Laporan Belajar</label>
            <textarea id="editContent" name="content" rows="6" required style="width: 100%; border: 1.5px solid var(--line); border-radius: 8px; padding: 10px 12px; font-family: inherit; font-size: 14px; margin-bottom: 12px; box-sizing: border-box; resize: vertical;"></textarea>

            <label for="editImage">Foto Dokumentasi (Ganti / Unggah Baru)</label>
            <input type="file" id="editImage" name="image" accept="image/*" class="no-tom-select" style="background: #FCFAF6; border: 1.5px solid var(--line, #E4DCCE); border-radius: 8px; padding: 10px 12px; font-size: 14px; font-family: inherit; color: var(--ink, #1B2A41); cursor: pointer; width: 100%; box-sizing: border-box;">
            
            <!-- Existing Image Preview -->
            <div id="editImagePreviewWrapper" style="margin-top: 12px; display: none;">
                <span style="font-size: 12px; color: var(--muted); display: block; margin-bottom: 4px;">Foto saat ini:</span>
                <img id="editImagePreview" src="" alt="Preview Dokumentasi" style="max-width: 200px; max-height: 120px; object-fit: cover; border-radius: 6px; border: 1.5px solid var(--line);">
            </div>
            
            <div class="actions-row" style="justify-content: flex-end; margin-top: 20px;">
                <button type="button" class="btn secondary" onclick="closeEditModal()">Batal</button>
                <button type="submit" class="btn">Simpan Perubahan</button>
            </div>
        </form>
    </div>
</div>
@endsection


