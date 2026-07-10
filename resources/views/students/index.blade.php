@extends('layouts.app')

@section('content')
<section class="panel active" id="tab-murid">
    <!-- Tambah Murid Card -->
    <div class="card">
        <h2>Tambah murid</h2>
        <p class="desc">Satu murid bisa punya satu mata pelajaran/kelas utama. Atur meeting awal jika murid sudah punya riwayat sebelumnya.</p>
        
        <form action="{{ route('students.store') }}" method="POST" autocomplete="off">
            @csrf
            <div class="row" style="margin-bottom: 16px;">
                <div>
                    <label for="newStudentName">Nama murid</label>
                    <input type="text" id="newStudentName" name="name" required placeholder="mis. Renziro" autocomplete="off">
                </div>
                <div>
                    <label for="newStudentSubject">Mata pelajaran / kelas</label>
                    <select id="newStudentSubject" name="subject" class="subject-select no-tom-select" required>
                        <option value="">Pilih atau ketik mata pelajaran…</option>
                        @foreach($subjects as $subj)
                            <option value="{{ $subj }}">{{ $subj }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            
            <div style="margin-bottom: 20px; background: #FCFAF6; border: 1px dashed var(--line); padding: 16px; border-radius: 8px; display: flex; align-items: center; gap: 20px; max-width: 620px;">
                <div style="flex-shrink: 0; width: 110px;">
                    <label for="newStudentMeeting" style="margin-bottom: 6px; display: block;">Meeting awal</label>
                    <input type="number" id="newStudentMeeting" name="meeting_count" min="0" value="0" style="margin: 0; text-align: center; font-weight: 600; width: 100%;">
                </div>
                <div style="font-size: 12.5px; color: var(--muted); line-height: 1.5; flex-grow: 1;">
                    <strong style="color: var(--ink);">Kustomisasi nomor urutan meeting:</strong><br>
                    Isi jika murid ini sudah melakukan beberapa meeting sebelumnya. Laporan baru berikutnya akan otomatis dimulai dari meeting ke-<strong>(nilai ini + 1)</strong>.
                </div>
            </div>
            
            <button type="submit" class="btn">Tambah murid</button>
        </form>
    </div>

    <!-- Daftar Murid Card -->
    <div class="card">
        <h2 style="margin-bottom: 10px;">Daftar murid</h2>

        {{-- Search bar --}}
        <form method="GET" action="{{ route('students.index') }}" class="search-bar">
            <input type="text" name="search" value="{{ $search ?? '' }}" placeholder="Cari nama atau mata pelajaran..." autocomplete="off">
            <button type="submit" class="btn-icon" title="Cari">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/>
                </svg>
            </button>
            @if($search)
                <a href="{{ route('students.index') }}" class="btn-icon" title="Hapus filter">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/>
                    </svg>
                </a>
            @endif
        </form>

        @if($students->isEmpty())
            <div class="empty">
                @if($search)
                    Tidak ada murid yang cocok dengan "{{ $search }}".
                @else
                    Belum ada murid. Tambah terlebih dahulu di atas.
                @endif
            </div>
        @else
            @foreach($students as $student)
                <div class="list-item">
                    <div class="meta">
                        <strong>{{ $student->name }}</strong>
                        <span>{{ $student->subject }} · sudah {{ $student->meeting_count }} meeting</span>
                    </div>
                    <div style="display: flex; gap: 6px; align-items: center; flex-shrink: 0;">
                        <span class="badge">M{{ $student->meeting_count + 1 }} berikutnya</span>
                        <a href="{{ route('history.student', $student->id) }}" class="btn secondary" style="padding: 6px 10px; font-size: 12px; text-decoration: none;">Riwayat</a>
                        <button class="btn secondary" style="padding: 6px 10px;" onclick="openEditModal('{{ $student->id }}', '{{ addslashes($student->name) }}', '{{ addslashes($student->subject) }}', {{ $student->meeting_count }})">Edit</button>
                        
                        <form action="{{ route('students.destroy', $student->id) }}" method="POST" onsubmit="return confirm('Hapus murid ini? Riwayat laporannya tetap tersimpan.')" style="margin:0;">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn danger" style="padding: 6px 10px;">Hapus</button>
                        </form>
                    </div>
                </div>
            @endforeach

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
            
            <div class="actions-row" style="justify-content: flex-end; margin-top: 20px;">
                <button type="button" class="btn secondary" onclick="closeEditModal()">Batal</button>
                <button type="submit" class="btn">Simpan</button>
            </div>
        </form>
    </div>
</div>
@endsection

@section('scripts')
<script>
    // ── Subject combobox: Tom Select with create ────────────────
    document.addEventListener('DOMContentLoaded', () => {
        document.querySelectorAll('.subject-select').forEach(el => {
            const ts = new TomSelect(el, {
                create: true,         // allow typing a brand-new subject
                maxItems: 1,
                openOnFocus: true,
                placeholder: 'Pilih atau ketik mata pelajaran…',
                createLabel: (input) => `<span>Tambah "<strong>${input}</strong>"</span>`,
                onItemAdd() { this.blur(); },
                render: {
                    no_results: () => '<div class="no-results">Tidak ditemukan — ketik untuk menambah baru.</div>',
                }
            });

            // Store reference for the edit modal
            el._tomSelect = ts;
        });
    });

    // ── Edit Modal ──────────────────────────────────────────────
    const editModal = document.getElementById('editModal');
    const editForm  = document.getElementById('editForm');
    const editName  = document.getElementById('editName');
    const editMeetingCount = document.getElementById('editMeetingCount');

    function openEditModal(id, name, subject, meetingCount) {
        editName.value = name;
        editMeetingCount.value = meetingCount;
        editForm.action = `/students/${id}`;

        // Set the Tom Select value for the edit subject field
        const editSubjectEl = document.getElementById('editSubject');
        const ts = editSubjectEl._tomSelect || editSubjectEl.tomselect;
        if (ts) {
            // If the subject doesn't exist as an option yet, add it first
            if (!ts.options[subject]) {
                ts.addOption({ value: subject, text: subject });
            }
            ts.setValue(subject, true); // true = silent (no change event)
        }

        editModal.classList.add('show');
    }

    function closeEditModal() {
        editModal.classList.remove('show');
    }

    // Close modal if user clicks outside modal-content
    editModal.addEventListener('click', function(e) {
        if (e.target === editModal) {
            closeEditModal();
        }
    });
</script>
@endsection
