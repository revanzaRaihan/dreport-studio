@extends('layouts.app')

@section('page_title', 'Jadwal Belajar Mingguan')
@section('page_description', 'Atur jadwal sesi kelas rutin mingguan untuk otomatisasi antrean laporan.')

@section('styles')
<style>
    /* Premium Row-Based Schedule Styling */
    .schedule-rows {
        display: flex;
        flex-direction: column;
        gap: 16px;
        margin-top: 20px;
    }
    .day-row {
        background: #FDFCF8;
        border: 1.5px solid var(--line, #E4DCCE);
        border-radius: 10px;
        padding: 16px;
        transition: all 0.2s ease;
    }
    .day-row:hover {
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.02);
    }
    .sessions-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(260px, 1fr));
        gap: 12px;
        margin-top: 10px;
    }
</style>
@endsection

@section('content')
<section class="panel active">
    <!-- Tambah Jadwal Card (Top Section) -->
    <div class="card" style="padding: 24px; margin-bottom: 24px;">
        <h2>Tambah Jadwal Sesi Les</h2>
        <p class="desc" style="margin-bottom: 20px;">Atur sesi pertemuan rutin mingguan Anda. Murid yang di-assign akan otomatis mendapatkan antrean laporan berkala setiap sesi selesai terlewati.</p>
        
        <form action="{{ route('schedule.store') }}" method="POST" autocomplete="off">
            @csrf
            
            <!-- Baris 1: Hari & Nama Ruangan -->
            <div class="row" style="margin-bottom: 16px;">
                <div style="flex: 1;">
                    <label for="newDayOfWeek" style="font-weight: 600;">Hari</label>
                    <select id="newDayOfWeek" name="day_of_week" required>
                        <option value="">Pilih Hari...</option>
                        @foreach($days as $num => $name)
                            <option value="{{ $num }}">{{ $name }}</option>
                        @endforeach
                    </select>
                </div>
                <div style="flex: 2;">
                    <label for="newLabel" style="font-weight: 600;">Label Ruangan / Nama Grup (Opsional)</label>
                    <input type="text" id="newLabel" name="label" placeholder="mis. Steve Jobs, Lt 1 R2" autocomplete="off">
                </div>
            </div>

            <!-- Baris 2: Waktu & Filter Murid -->
            <div class="row" style="margin-bottom: 16px;">
                <div style="flex: 1;">
                    <label for="newStartTime" style="font-weight: 600;">Jam Mulai</label>
                    <input type="time" id="newStartTime" name="start_time" required>
                </div>
                <div style="flex: 1;">
                    <label for="newEndTime" style="font-weight: 600;">Jam Selesai</label>
                    <input type="time" id="newEndTime" name="end_time" required>
                </div>
                <div style="flex: 2;">
                    <label style="font-weight: 600;">Pencarian &amp; Filter Murid</label>
                    <div style="display: flex; gap: 8px;">
                        <input type="text" id="studentSearch" placeholder="Cari nama murid..." autocomplete="off" style="margin: 0; flex: 1;">
                        <select id="subjectFilter" style="margin: 0; flex: 1; min-width: 140px;">
                            <option value="">Semua Mapel</option>
                            @foreach($subjects as $subj)
                                <option value="{{ $subj }}">{{ $subj }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
            </div>

            <!-- Baris 3: Assign Murid -->
            <div class="form-group" style="margin-bottom: 20px;">
                <label style="font-weight: 600;">Assign Murid ke Sesi Ini</label>
                <div style="max-height: 120px; overflow-y: auto; border: 1.5px solid var(--line); border-radius: 8px; padding: 10px; background: #FCFAF6; box-sizing: border-box; display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: 8px;">
                    @if($students->isEmpty())
                        <span style="font-size: 12px; color: var(--muted); font-style: italic; grid-column: 1/-1;">Belum ada data murid. Tambah murid terlebih dahulu di menu Murid.</span>
                    @else
                        @foreach($students as $student)
                            <label class="student-checkbox-item" data-name="{{ $student->name }}" data-subject="{{ $student->subject }}" style="display: flex; align-items: center; gap: 8px; font-weight: normal; margin: 0; cursor: pointer; font-size: 13px; color: var(--ink);">
                                <input type="checkbox" name="student_ids[]" value="{{ $student->id }}" style="margin: 0; width: auto; accent-color: var(--teal);">
                                <span>{{ $student->name }} ({{ $student->subject }})</span>
                            </label>
                        @endforeach
                    @endif
                </div>
            </div>

            <button type="submit" class="btn" {{ $students->isEmpty() ? 'disabled' : '' }}>Tambah Jadwal</button>
        </form>
    </div>

    <!-- Papan Jadwal Mingguan (Bottom Section) -->
    <div class="card" style="padding: 24px;">
        <h2>Papan Jadwal Sesi Les Mingguan</h2>
        <p class="desc">Daftar sesi belajar mingguan Senin - Sabtu yang berjalan saat ini (Minggu kantor tutup).</p>

        <div class="schedule-rows">
            @foreach($days as $dayNum => $dayName)
                @php
                    $daySchedules = $schedules->where('day_of_week', $dayNum);
                @endphp
                <div class="day-row">
                    <div style="display: flex; justify-content: space-between; align-items: center; border-bottom: 1.5px solid var(--line); padding-bottom: 8px; margin-bottom: 8px;">
                        <h3 style="font-family: 'Space Grotesk', sans-serif; font-size: 14.5px; font-weight: 700; color: var(--ink); margin: 0;">
                            {{ $dayName }}
                        </h3>
                        <span style="font-size: 11.5px; color: var(--muted); font-weight: 600;">
                            {{ $daySchedules->count() }} Sesi Terjadwal
                        </span>
                    </div>
                    
                    @if($daySchedules->isEmpty())
                        <div style="font-size: 12px; color: var(--muted); padding: 8px 0; font-style: italic;">Tidak ada sesi belajar terjadwal.</div>
                    @else
                        <div class="sessions-grid">
                            @foreach($daySchedules as $sched)
                                <div class="schedule-card" style="background: #FFFFFF; border: 1px solid var(--line); border-left: 4px solid var(--teal); border-radius: 8px; padding: 14px; font-size: 12.5px; box-shadow: 0 1px 3px rgba(0,0,0,0.02); display: flex; flex-direction: column; justify-content: space-between; min-height: 110px;">
                                    <div>
                                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px;">
                                            <span style="font-weight: 700; color: var(--teal); font-size: 13px;">
                                                {{ substr($sched->start_time, 0, 5) }} - {{ substr($sched->end_time, 0, 5) }}
                                            </span>
                                            @if($sched->label)
                                                <span style="font-size: 9.5px; font-weight: 700; color: var(--muted); text-transform: uppercase; background: #FAF9F6; border: 1px solid var(--line); padding: 2px 8px; border-radius: 4px;">
                                                    {{ $sched->label }}
                                                </span>
                                            @endif
                                        </div>
                                        
                                        <div style="margin-bottom: 8px;">
                                            @if($sched->students->isEmpty())
                                                <span style="color: var(--red); font-style: italic; font-size: 11.5px;">Belum ada murid</span>
                                            @else
                                                <div style="display: flex; flex-wrap: wrap; gap: 6px; margin-top: 6px;">
                                                    @foreach($sched->students as $std)
                                                        <span style="font-size: 11px; background: #FCFAF6; border: 1px solid var(--line); padding: 3px 8px; border-radius: 4px; color: var(--ink); font-weight: 600; display: inline-flex; align-items: center;">
                                                            {{ $std->name }}
                                                            <span style="font-weight: normal; color: var(--muted); font-size: 9.5px; margin-left: 4px;">({{ $std->subject }})</span>
                                                        </span>
                                                    @endforeach
                                                </div>
                                            @endif
                                        </div>
                                    </div>

                                    <div style="display: flex; gap: 6px; justify-content: flex-end; border-top: 1px solid color-mix(in srgb, var(--line) 40%, transparent); padding-top: 8px; margin-top: 8px;">
                                        <button class="btn secondary btn-edit-sched" style="padding: 4px 8px; font-size: 11px;"
                                                data-id="{{ $sched->id }}"
                                                data-day="{{ $sched->day_of_week }}"
                                                data-start="{{ substr($sched->start_time, 0, 5) }}"
                                                data-end="{{ substr($sched->end_time, 0, 5) }}"
                                                data-label="{{ $sched->label }}"
                                                data-students="{{ json_encode($sched->students->pluck('id')) }}">
                                            Edit
                                        </button>
                                        
                                        <form action="{{ route('schedule.destroy', $sched->id) }}" method="POST" onsubmit="return confirm('Hapus jadwal sesi ini?')" style="margin:0;">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn danger" style="padding: 4px 8px; font-size: 11px;">Hapus</button>
                                        </form>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>
            @endforeach
        </div>
    </div>
</section>

<!-- Edit Schedule Modal -->
<div class="modal-backdrop" id="editScheduleModal">
    <div class="modal-content" style="max-width: 500px; width: 90%;">
        <h2 style="font-family: 'Space Grotesk', sans-serif; font-size: 16px; font-weight: 600; margin-bottom: 8px;">Edit Sesi Jadwal</h2>
        <p class="desc" style="margin-bottom: 16px;">Ubah rincian waktu, label ruangan, atau daftar murid yang mengikuti sesi ini.</p>
        
        <form id="editScheduleForm" method="POST">
            @csrf
            @method('PUT')
            
            <div class="row" style="margin-bottom: 14px;">
                <div>
                    <label for="editDayOfWeek">Hari</label>
                    <select id="editDayOfWeek" name="day_of_week" required>
                        @foreach($days as $num => $name)
                            <option value="{{ $num }}">{{ $name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label for="editLabel">Label Ruangan (Opsional)</label>
                    <input type="text" id="editLabel" name="label" placeholder="mis. Steve Jobs, Lt 1 R2" autocomplete="off">
                </div>
            </div>

            <div class="row" style="margin-bottom: 14px;">
                <div>
                    <label for="editStartTime">Jam Mulai</label>
                    <input type="time" id="editStartTime" name="start_time" required>
                </div>
                <div>
                    <label for="editEndTime">Jam Selesai</label>
                    <input type="time" id="editEndTime" name="end_time" required>
                </div>
            </div>

            <div class="row" style="margin-bottom: 16px;">
                <div>
                    <label>Pencarian &amp; Filter Murid</label>
                    <input type="text" id="editStudentSearch" placeholder="Cari nama murid..." autocomplete="off" style="margin-bottom: 10px;">
                    <select id="editSubjectFilter">
                        <option value="">Semua Mata Pelajaran / Kelas</option>
                        @foreach($subjects as $subj)
                            <option value="{{ $subj }}">{{ $subj }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label>Assign Murid ke Sesi Ini</label>
                    <div style="max-height: 120px; overflow-y: auto; border: 1.5px solid var(--line); border-radius: 8px; padding: 10px; background: #FCFAF6; box-sizing: border-box;">
                        @foreach($students as $student)
                            <label class="edit-student-checkbox-item" data-name="{{ $student->name }}" data-subject="{{ $student->subject }}" style="display: flex; align-items: center; gap: 8px; font-weight: normal; margin-bottom: 6px; cursor: pointer; font-size: 13px; color: var(--ink);">
                                <input type="checkbox" name="student_ids[]" value="{{ $student->id }}" class="edit-student-checkbox" style="margin: 0; width: auto; accent-color: var(--teal);">
                                <span>{{ $student->name }} ({{ $student->subject }})</span>
                            </label>
                        @endforeach
                    </div>
                </div>
            </div>

            <div class="actions-row" style="justify-content: flex-end; margin-top: 20px;">
                <button type="button" class="btn secondary" onclick="closeEditModal()">Batal</button>
                <button type="submit" class="btn">Simpan Perubahan</button>
            </div>
        </form>
    </div>
</div>
@endsection

@section('scripts')
<script>
    // --- Add Form Student Search & Filter ---
    const studentSearch = document.getElementById('studentSearch');
    const subjectFilter = document.getElementById('subjectFilter');
    const studentItems = document.querySelectorAll('.student-checkbox-item');

    function filterStudents() {
        const query = studentSearch.value.toLowerCase().trim();
        const selectedSubject = subjectFilter.value;

        studentItems.forEach(item => {
            const name = item.dataset.name.toLowerCase();
            const subject = item.dataset.subject;

            const matchesQuery = name.includes(query);
            const matchesSubject = !selectedSubject || subject === selectedSubject;

            if (matchesQuery && matchesSubject) {
                item.style.display = 'flex';
            } else {
                item.style.display = 'none';
            }
        });
    }

    if (studentSearch && subjectFilter) {
        studentSearch.addEventListener('input', filterStudents);
        subjectFilter.addEventListener('change', filterStudents);
    }

    // --- Edit Form Student Search & Filter ---
    const editStudentSearch = document.getElementById('editStudentSearch');
    const editSubjectFilter = document.getElementById('editSubjectFilter');
    const editStudentItems = document.querySelectorAll('.edit-student-checkbox-item');

    function filterEditStudents() {
        const query = editStudentSearch.value.toLowerCase().trim();
        const selectedSubject = editSubjectFilter.value;

        editStudentItems.forEach(item => {
            const name = item.dataset.name.toLowerCase();
            const subject = item.dataset.subject;

            const matchesQuery = name.includes(query);
            const matchesSubject = !selectedSubject || subject === selectedSubject;

            if (matchesQuery && matchesSubject) {
                item.style.display = 'flex';
            } else {
                item.style.display = 'none';
            }
        });
    }

    if (editStudentSearch && editSubjectFilter) {
        editStudentSearch.addEventListener('input', filterEditStudents);
        editSubjectFilter.addEventListener('change', filterEditStudents);
    }

    // --- Edit Schedule Modal Controls ---
    const editModal = document.getElementById('editScheduleModal');
    const editForm = document.getElementById('editScheduleForm');
    const editDayOfWeek = document.getElementById('editDayOfWeek');
    const editStartTime = document.getElementById('editStartTime');
    const editEndTime = document.getElementById('editEndTime');
    const editLabel = document.getElementById('editLabel');
    const editCheckboxes = document.querySelectorAll('.edit-student-checkbox');

    function openEditModal(id, day, start, end, label, studentIds) {
        editForm.action = `/schedule/${id}`;
        editDayOfWeek.value = day;
        editStartTime.value = start;
        editEndTime.value = end;
        editLabel.value = label || '';

        // Reset search/filter fields when opening
        if (editStudentSearch) editStudentSearch.value = '';
        if (editSubjectFilter) editSubjectFilter.value = '';
        filterEditStudents();

        // Reset and check boxes
        editCheckboxes.forEach(cb => {
            cb.checked = studentIds.includes(cb.value);
        });

        editModal.classList.add('show');
    }

    function closeEditModal() {
        editModal.classList.remove('show');
    }

    editModal.addEventListener('click', function(e) {
        if (e.target === editModal) {
            closeEditModal();
        }
    });

    document.querySelectorAll('.btn-edit-sched').forEach(btn => {
        btn.addEventListener('click', function() {
            const id = this.dataset.id;
            const day = this.dataset.day;
            const start = this.dataset.start;
            const end = this.dataset.end;
            const label = this.dataset.label;
            const studentIds = JSON.parse(this.dataset.students || '[]');

            openEditModal(id, day, start, end, label, studentIds);
        });
    });
</script>
@endsection
