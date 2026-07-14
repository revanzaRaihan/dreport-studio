@extends('layouts.app')

@section('page_title', 'Daftar Antrean Laporan')
@section('page_description', 'Pantau daftar sesi belajar yang sudah selesai dan menunggu pembuatan laporan.')

@section('content')
<section class="panel active" id="tab-listing-report">
    <!-- Tambah Listing Report Card -->
    <div class="card">
        <h2>Tambah listing report</h2>
        <p class="desc">Tambahkan antrean laporan yang belum Anda buat untuk mempermudah pelacakan. Data ini nantinya bisa dipilih saat membuat laporan.</p>
        
        <form action="{{ route('pending-reports.store') }}" method="POST" autocomplete="off">
            @csrf
            <div class="row" style="margin-bottom: 16px;">
                <div>
                    <label for="newStudentId">Nama murid</label>
                    <select id="newStudentId" name="student_id" required>
                        <option value="">Pilih murid…</option>
                        @foreach($students as $student)
                            <option value="{{ $student->id }}">{{ $student->name }} — {{ $student->subject }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label for="newMeetingNumber">Pertemuan Ke-</label>
                    <input type="number" id="newMeetingNumber" name="meeting_number" min="1" required placeholder="mis. 3" autocomplete="off">
                </div>
                <div>
                    <label for="newReportDate">Tanggal pertemuan</label>
                    <input type="date" id="newReportDate" name="report_date" required value="{{ date('Y-m-d') }}">
                </div>
            </div>
            
            <button type="submit" class="btn">Tambah listing</button>
        </form>
    </div>

    <!-- Daftar Listing Report Card -->
    <div class="card">
        <h2 style="margin-bottom: 10px;">Daftar antrean laporan (Belum Dibuat)</h2>

        {{-- Search bar --}}
        <form method="GET" action="{{ route('pending-reports.index') }}" class="search-bar">
            <input type="text" name="search" value="{{ $search ?? '' }}" placeholder="Cari nama murid atau subjek..." autocomplete="off">
            <button type="submit" class="btn-icon" title="Cari">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/>
                </svg>
            </button>
            @if($search)
                <a href="{{ route('pending-reports.index') }}" class="btn-icon" title="Hapus filter">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/>
                    </svg>
                </a>
            @endif
        </form>

        @if($studentsWithPending->isEmpty())
            <div class="empty">
                @if($search)
                    Tidak ada listing report yang cocok dengan "{{ $search }}".
                @else
                    Belum ada listing report yang belum dibuat.
                @endif
            </div>
        @else
            @foreach($studentsWithPending as $student)
                <div class="list-item" style="flex-direction: column; align-items: flex-start; gap: 12px; padding: 16px 20px;">
                    <div class="meta" style="margin-bottom: 4px;">
                        <strong style="font-size: 15px; color: var(--ink);">{{ $student->name }}</strong>
                        <span style="font-size: 12px; color: var(--muted);">{{ $student->subject }}</span>
                    </div>
                    
                    <div style="width: 100%; display: flex; flex-direction: column; gap: 8px; border-left: 2.5px solid var(--line, #E4DCCE); padding-left: 14px; margin-left: 4px;">
                        @foreach($student->pendingReports as $pending)
                            <div style="display: flex; justify-content: space-between; align-items: center; width: 100%; gap: 12px;">
                                <div style="font-size: 13.5px; color: var(--ink, #1B2A41);">
                                    Pertemuan Ke-<strong style="color: var(--teal, #2F8F7E);">{{ $pending->meeting_number }}</strong> · Tanggal: <span style="font-weight: 500;">{{ $pending->report_date ? $pending->report_date->format('d M Y') : '-' }}</span>
                                </div>
                                <div style="display: flex; gap: 6px; align-items: center; flex-shrink: 0;">
                                    <a href="{{ route('report.index') }}?student_id={{ $pending->student_id }}&pending_report_id={{ $pending->id }}" class="btn" style="padding: 4px 8px; font-size: 11.5px; text-decoration: none; display: inline-block;">Tulis Laporan</a>
                                    
                                    <button class="btn secondary" style="padding: 4px 8px; font-size: 11.5px;" onclick="openEditModal('{{ $pending->id }}', '{{ $pending->student_id }}', {{ $pending->meeting_number }}, '{{ $pending->report_date ? $pending->report_date->format('Y-m-d') : '' }}')">Edit</button>
                                    
                                    <button type="button" class="btn danger" style="padding: 4px 8px; font-size: 11.5px;" onclick="openDeleteModal('{{ route('pending-reports.destroy', $pending->id) }}', 'Hapus listing report ini?')">Hapus</button>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endforeach

            <x-pagination :paginator="$studentsWithPending" />
        @endif
    </div>
</section>

<!-- Edit Modal -->
<div class="modal-backdrop" id="editModal">
    <div class="modal-content">
        <h2 style="font-family: 'Space Grotesk', sans-serif; font-size: 16px; font-weight: 600; margin-bottom: 8px;">Edit Listing Report</h2>
        <p class="desc" style="margin-bottom: 16px;">Ubah data murid, pertemuan ke-berapa, atau tanggal pertemuan listing.</p>
        
        <form id="editForm" method="POST">
            @csrf
            @method('PUT')
            
            <label for="editStudentId">Nama Murid</label>
            <select id="editStudentId" name="student_id" required>
                <option value="">Pilih murid…</option>
                @foreach($students as $student)
                    <option value="{{ $student->id }}">{{ $student->name }} — {{ $student->subject }}</option>
                @endforeach
            </select>
            
            <label for="editMeetingNumber">Pertemuan Ke-</label>
            <input type="number" id="editMeetingNumber" name="meeting_number" min="1" required>

            <label for="editReportDate">Tanggal pertemuan</label>
            <input type="date" id="editReportDate" name="report_date" required>
            
            <div class="actions-row" style="justify-content: flex-end; margin-top: 20px;">
                <button type="button" class="btn secondary" onclick="closeEditModal()">Batal</button>
                <button type="submit" class="btn">Simpan</button>
            </div>
        </form>
    </div>
</div>
@endsection


