@extends('layouts.app')

@section('page_title', 'Manajemen Murid')
@section('page_description', 'Kelola data murid, mata pelajaran, dan parameter awal pembelajaran.')



@section('content')
<section class="panel active" id="tab-murid">
        <!-- Kolom Kiri: Tambah Murid Card -->
        <div class="card">
            <h2 style="margin-bottom: 4px;">Tambah murid</h2>
            <p class="desc" style="margin-bottom: 20px;">Satu murid terikat ke satu mata pelajaran/kelas utama. Atur parameter awal jika ada riwayat belajar sebelumnya.</p>
            
            <form action="{{ route('students.store') }}" method="POST" autocomplete="off">
                @csrf
                <div class="form-group" style="margin-bottom: 16px;">
                    <label for="newStudentName" style="font-weight: 600;">Nama murid</label>
                    <input type="text" id="newStudentName" name="name" required placeholder="mis. Renziro" autocomplete="off" style="margin: 0;">
                </div>

                <div class="form-group" style="margin-bottom: 20px;">
                    <label for="newStudentSubject" style="font-weight: 600;">Mata pelajaran / kelas</label>
                    <select id="newStudentSubject" name="subject" class="subject-select no-tom-select" required>
                        <option value="">Pilih atau ketik mata pelajaran…</option>
                        @foreach($subjects as $subj)
                            <option value="{{ $subj }}">{{ $subj }}</option>
                        @endforeach
                    </select>
                </div>
                
                <div class="param-block">
                    <div class="param-row">
                        <div>
                            <label for="newStudentMeeting" style="margin-bottom: 6px; display: block; font-weight: 600; font-size: 12px;">Meeting awal</label>
                            <input type="number" id="newStudentMeeting" name="meeting_count" min="0" value="0" style="margin: 0; text-align: center; font-weight: 600; width: 100%;">
                        </div>
                        <div>
                            <label for="newStudentFirstMeeting" style="margin-bottom: 6px; display: block; font-weight: 600; font-size: 12px;">Pertemuan pertama</label>
                            <input type="date" id="newStudentFirstMeeting" name="first_meeting_date" style="margin: 0; font-weight: 600; width: 100%; font-size: 13px;">
                        </div>
                    </div>
                    <div style="font-size: 12px; color: var(--muted); line-height: 1.5; border-top: 1px solid var(--line); padding-top: 10px; margin-top: 4px;">
                        <strong style="color: var(--ink);">Parameter Awal:</strong><br>
                        Penting untuk menentukan nomor urut pertemuan berikutnya dan otomatisasi sinkronisasi jadwal mingguan.
                    </div>
                </div>
                
                <button type="submit" class="btn" style="width: 100%; margin: 0;">Tambah murid baru</button>
            </form>
        </div>

        <!-- Kolom Kanan: Daftar Murid Card -->
        <div class="card">
            <div style="display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 12px; margin-bottom: 16px; border-bottom: 1.5px solid var(--line); padding-bottom: 12px;">
                <h2 style="margin: 0;">Daftar murid</h2>
                {{-- Search bar --}}
                <form method="GET" action="{{ route('students.index') }}" class="search-bar" style="margin: 0; width: 260px;">
                    <input type="text" name="search" value="{{ $search ?? '' }}" placeholder="Cari nama murid..." autocomplete="off">
                    <button type="submit" class="btn-icon" title="Cari">
                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/>
                        </svg>
                    </button>
                    @if($search)
                        <a href="{{ route('students.index') }}" class="btn-icon" title="Hapus filter">
                            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/>
                            </svg>
                        </a>
                    @endif
                </form>
            </div>

            @if($students->isEmpty())
                <div class="empty">
                    @if($search)
                        Tidak ada murid yang cocok dengan "{{ $search }}".
                    @else
                        Belum ada murid. Tambah terlebih dahulu di kolom kiri.
                    @endif
                </div>
            @else
                <div style="display: flex; flex-direction: column; gap: 12px; margin-bottom: 16px;">
                    @foreach($students as $student)
                        <div class="list-item" style="padding: 12px 14px;">
                            <div class="meta">
                                <strong style="font-size: 14.5px;">{{ $student->name }}</strong>
                                <span style="font-size: 12px; margin-top: 2px;">
                                    {{ $student->subject }} · sudah {{ $student->meeting_count }} meeting
                                    @if($student->first_meeting_date)
                                        · mulai {{ $student->first_meeting_date->format('d M Y') }}
                                    @endif
                                </span>
                            </div>
                            <div style="display: flex; gap: 6px; align-items: center; flex-shrink: 0; flex-wrap: wrap;">
                                <span class="badge" style="font-size: 11px; padding: 2px 6px; border-radius: 4px;">M{{ $student->meeting_count + 1 }} berikutnya</span>
                                <a href="{{ route('history.student', $student->id) }}" class="btn secondary" style="padding: 6px 10px; font-size: 12px; text-decoration: none;">Riwayat</a>
                                <button class="btn secondary" style="padding: 6px 10px; font-size: 12px;" onclick="openEditModal('{{ $student->id }}', '{{ addslashes($student->name) }}', '{{ addslashes($student->subject) }}', {{ $student->meeting_count }}, '{{ $student->first_meeting_date ? $student->first_meeting_date->format('Y-m-d') : '' }}')">Edit</button>
                                <button type="button" class="btn danger" style="padding: 6px 10px; font-size: 12px;" onclick="openDeleteModal('{{ route('students.destroy', $student->id) }}', 'Hapus murid ini? Riwayat laporannya tetap tersimpan.')">Hapus</button>
                            </div>
                        </div>
                    @endforeach
                </div>

                <x-pagination :paginator="$students" />
            @endif
        </div>
</section>


<!-- Edit Student Modal (Premium Modal) -->
<div class="modal-backdrop" id="editModal">
    <div class="modal-content">
        <h2 style="font-family: 'Space Grotesk', sans-serif; font-size: 16px; font-weight: 600; margin-bottom: 8px;">Edit Data Murid</h2>
        <p class="desc" style="margin-bottom: 16px;">Ubah nama, kelas, atau koreksi jumlah meeting yang sudah berjalan.</p>
        
        <form id="editForm" method="POST">
            @csrf
            @method('PUT')
            
            <label for="editName">Nama Murid</label>
            <input type="text" id="editName" name="name" required autocomplete="off">
            
            <label for="editSubject">Mata Pelajaran / Kelas</label>
            <select id="editSubject" name="subject" class="subject-select no-tom-select" required>
                <option value="">Pilih atau ketik mata pelajaran…</option>
                @foreach($subjects as $subj)
                    <option value="{{ $subj }}">{{ $subj }}</option>
                @endforeach
            </select>

            <label for="editMeetingCount">Jumlah meeting yang sudah berjalan</label>
            <input type="number" id="editMeetingCount" name="meeting_count" min="0" required>
            
            <label for="editFirstMeetingDate">Tanggal pertemuan pertama (Opsional)</label>
            <input type="date" id="editFirstMeetingDate" name="first_meeting_date">

            <div class="actions-row" style="justify-content: flex-end; margin-top: 20px;">
                <button type="button" class="btn secondary" onclick="closeEditModal()">Batal</button>
                <button type="submit" class="btn">Simpan</button>
            </div>
        </form>
    </div>
</div>
@endsection


