@extends('layouts.app')

@section('content')
<section class="panel active" id="tab-buat">
    @if($datasetCount === 0)
        <div class="alert alert-danger" style="margin-bottom: 20px;">
            <strong>Peringatan:</strong> Kamu belum menambahkan contoh laporan di tab <strong>Dataset Gaya</strong>. AI membutuhkan setidaknya 1 contoh laporan lama milikmu sebagai referensi few-shot agar hasilnya mirip gaya tulisanmu.
        </div>
    @endif

    <div class="card">
        <h2>Laporan baru</h2>
        <p class="desc">Pilih murid, isi materi &amp; behavior. Tanggal dan nomor meeting terisi otomatis dari data tracking.</p>

        <div class="form-group">
            <label for="genStudent">Murid</label>
            <select id="genStudent" class="no-tom-select student-picker">
                <option value=""></option>
                @foreach($students as $student)
                    <option value="{{ $student->id }}">{{ $student->name }} — {{ $student->subject }}</option>
                @endforeach
            </select>
        </div>

        <div class="row">
            <div id="datepickerWrapper" style="position: relative;">
                <label for="genDateDisplay">Tanggal</label>
                <input type="text" id="genDateDisplay" readonly style="cursor: pointer; background: #FCFAF6;" placeholder="Pilih Tanggal...">
                <input type="hidden" id="genDate" value="{{ date('Y-m-d') }}">
            </div>
            <div>
                <label for="genMeeting">Meeting ke-</label>
                <input type="text" id="genMeeting" placeholder="mis. 5" autocomplete="off">
            </div>
        </div>

        <div class="form-group">
            <label for="genMateri">Materi</label>
            <input type="text" id="genMateri" placeholder="mis. For Loop, penutupan array method, dst." autocomplete="off">
        </div>

        <div class="form-group">
            <label for="genBehavior">Behavior murid</label>
            <textarea id="genBehavior" placeholder="mis. bisa menjelaskan kembali, agak lambat di bagian logika kondisional, aktif bertanya" autocomplete="off"></textarea>
        </div>

        <button class="btn" id="btnGenerate" {{ $datasetCount === 0 ? 'disabled' : '' }}>
            <span class="btn-spinner spinner"></span>
            <span class="btn-text">Generate laporan</span>
        </button>
        <div class="status-line" id="genStatus"></div>
    </div>

    <!-- Output Card (Hasil) -->
    <div class="card" id="outputCard" style="display:none;">
        <h2>Hasil</h2>
        <p class="desc">Kamu bisa mengedit teks di bawah secara langsung sebelum menyimpannya.</p>
        <div class="output-box" id="outputBox" contenteditable="true"></div>
        
        <div class="actions-row">
            <button class="btn secondary" id="btnCopy">Copy ke clipboard</button>
            <button class="btn" id="btnSave">Simpan ke riwayat</button>
            <button class="btn secondary" id="btnRegenerate">Generate ulang</button>
        </div>
    </div>
</section>

<!-- Hidden form for saving report -->
<form id="saveReportForm" action="{{ route('report.store') }}" method="POST" style="display: none;">
    @csrf
    <input type="hidden" name="student_id" id="saveStudentId">
    <input type="hidden" name="meeting_number" id="saveMeetingNumber">
    <input type="hidden" name="report_date" id="saveReportDate">
    <input type="hidden" name="materi" id="saveMateri">
    <input type="hidden" name="behavior" id="saveBehavior">
    <input type="hidden" name="content" id="saveContent">
</form>
@endsection

@section('scripts')
<script>
    // Meeting numbers mapping (from PHP to JS)
    const meetingNumbers = @json($meetingNumbers);

    const selectStudent = document.getElementById('genStudent');
    const inputMeeting  = document.getElementById('genMeeting');
    const btnGenerate   = document.getElementById('btnGenerate');
    const btnRegenerate = document.getElementById('btnRegenerate');
    const btnCopy       = document.getElementById('btnCopy');
    const btnSave       = document.getElementById('btnSave');
    const statusLine    = document.getElementById('genStatus');
    const outputCard    = document.getElementById('outputCard');
    const outputBox     = document.getElementById('outputBox');

    // ── Student picker — clean input-style Tom Select ────────────
    document.addEventListener('DOMContentLoaded', () => {
        const ts = new TomSelect(selectStudent, {
            create: false,
            maxItems: 1,
            openOnFocus: true,
            selectOnTab: true,
            placeholder: 'Pilih murid…',
            render: {
                no_results: () => '<div style="padding:10px 14px;color:var(--muted);">Tidak ada murid yang cocok.</div>',
            },
            onChange(studentId) {
                // Update placeholder to show selected name (item tag is hidden via CSS)
                const opt = this.options[studentId];
                this.control_input.placeholder = opt ? opt.text : 'Pilih murid…';
                this.control_input.value = '';

                if (studentId && meetingNumbers[studentId] !== undefined) {
                    inputMeeting.value = meetingNumbers[studentId];
                } else {
                    inputMeeting.value = '';
                }
                selectStudent.dispatchEvent(new Event('change', { bubbles: true }));
                this.blur();
            }
        });

        ts.control_input.placeholder = 'Pilih murid…';
    });

    // ── MCDatepicker — Material Design Date Picker ────────────
    document.addEventListener('DOMContentLoaded', () => {
        const wrapper = document.getElementById('datepickerWrapper');
        const displayInput = document.getElementById('genDateDisplay');
        const months = ['Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];

        const picker = MCDatepicker.create({
            el: '#genDateDisplay', // bind to input field
            bodyType: 'inline', // render inline
            context: wrapper, // render inside relative wrapper context
            selectedDate: new Date(), // preselect today's date
            dateFormat: 'dd mmmm yyyy', // format as e.g. "9 Juli 2026"
            customMonths: months,
            customWeekDays: ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'],
            firstWeekday: 0, // starts on Sunday (Minggu)
            customOkBTN: 'OK',
            customCancelBTN: 'BATAL',
            customClearBTN: 'HAPUS',
            autoClose: true
        });

        // Position and toggle the calendar as a fixed-position dropdown under the input
        const showCalendar = () => {
            const calendarEl = document.querySelector('.mc-calendar');
            if (!calendarEl) return;
            const rect = displayInput.getBoundingClientRect();
            calendarEl.style.position = 'fixed';
            calendarEl.style.top = (rect.bottom + 4) + 'px';
            calendarEl.style.left = rect.left + 'px';
            calendarEl.style.zIndex = '9999';
            calendarEl.style.display = 'block';
        };

        const hideCalendar = () => {
            const calendarEl = document.querySelector('.mc-calendar');
            if (calendarEl) calendarEl.style.display = 'none';
        };

        displayInput.addEventListener('click', (e) => {
            e.stopPropagation();
            const calendarEl = document.querySelector('.mc-calendar');
            if (calendarEl && calendarEl.style.display === 'block') {
                hideCalendar();
            } else {
                showCalendar();
            }
        });

        // Update the display input and hidden field when a date is selected and close dropdown
        picker.onSelect((date) => {
            if (!date) return;
            const yyyy = date.getFullYear();
            const mm = String(date.getMonth() + 1).padStart(2, '0');
            const dd = String(date.getDate()).padStart(2, '0');
            
            // Update display text
            const selectedFormatted = `${date.getDate()} ${months[date.getMonth()]} ${yyyy}`;
            displayInput.value = selectedFormatted;
            
            // Update hidden field for server
            document.getElementById('genDate').value = `${yyyy}-${mm}-${dd}`;
            
            hideCalendar();
        });

        picker.onCancel(() => {
            hideCalendar();
        });

        // Close when clicking outside of calendar container
        document.addEventListener('click', (e) => {
            const calendarEl = document.querySelector('.mc-calendar');
            if (calendarEl && !calendarEl.contains(e.target) && e.target !== displayInput) {
                hideCalendar();
            }
        });

        // Set initial display date formatted in Indonesian on load
        const today = new Date();
        const formattedToday = `${today.getDate()} ${months[today.getMonth()]} ${today.getFullYear()}`;
        displayInput.value = formattedToday;
    });


    // Auto-update meeting field when student is selected (fallback for native change)
    selectStudent.addEventListener('change', function() {
        const studentId = this.value;
        if (studentId && meetingNumbers[studentId]) {
            inputMeeting.value = meetingNumbers[studentId];
        } else {
            inputMeeting.value = '';
        }
    });

    // Generate report logic
    async function generateReport() {
        const studentId = selectStudent.value;
        const reportDate = document.getElementById('genDate').value;
        const meetingNumber = inputMeeting.value.trim();
        const materi = document.getElementById('genMateri').value.trim();
        const behavior = document.getElementById('genBehavior').value.trim();

        // Validation
        if (!studentId) {
            showStatus('Silakan pilih murid terlebih dahulu.', 'err');
            return;
        }
        if (!materi || !behavior) {
            showStatus('Materi dan behavior harus diisi.', 'err');
            return;
        }
        if (!meetingNumber || isNaN(meetingNumber)) {
            showStatus('Nomor meeting harus diisi dengan angka.', 'err');
            return;
        }

        // Loading UI state
        btnGenerate.disabled = true;
        btnGenerate.classList.add('loading');
        btnRegenerate.disabled = true;
        showStatus('Sedang men-generate laporan...', '');

        try {
            const response = await fetch('{{ route("report.generate") }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: JSON.stringify({
                    student_id: studentId,
                    report_date: reportDate,
                    meeting_number: meetingNumber,
                    materi: materi,
                    behavior: behavior
                })
            });

            const data = await response.json();

            if (!response.ok) {
                throw new Error(data.message || 'Terjadi kesalahan sistem.');
            }

            // Populate output view and metadata
            outputBox.textContent = data.text;
            outputBox.dataset.studentId = data.student_id;
            outputBox.dataset.studentName = data.student_name;
            outputBox.dataset.subject = data.subject;
            outputBox.dataset.meetingNumber = data.meeting_number;
            outputBox.dataset.reportDate = data.report_date;
            outputBox.dataset.materi = data.materi;
            outputBox.dataset.behavior = data.behavior;

            // Show card
            outputCard.style.display = 'block';
            showStatus('Berhasil di-generate. Cek & edit jika perlu sebelum disimpan.', 'ok');
            
            // Scroll output card into view
            outputCard.scrollIntoView({ behavior: 'smooth', block: 'nearest' });

        } catch (error) {
            showStatus(error.message, 'err');
        } finally {
            btnGenerate.disabled = false;
            btnGenerate.classList.remove('loading');
            btnRegenerate.disabled = false;
        }
    }

    btnGenerate.addEventListener('click', generateReport);
    btnRegenerate.addEventListener('click', generateReport);

    // Helpers
    function showStatus(msg, type) {
        statusLine.textContent = msg;
        statusLine.className = 'status-line';
        if (type === 'err') statusLine.classList.add('err');
        if (type === 'ok') statusLine.classList.add('ok');
    }

    // Copy to clipboard
    btnCopy.addEventListener('click', function() {
        const text = outputBox.textContent;
        navigator.clipboard.writeText(text)
            .then(() => showToast('Disalin ke clipboard'))
            .catch(() => showToast('Gagal menyalin. Silakan copy manual.'));
    });

    // Save report to database
    btnSave.addEventListener('click', function() {
        const text = outputBox.textContent.trim();
        if (!text) {
            showToast('Konten laporan kosong.');
            return;
        }

        // Map values to hidden form inputs
        document.getElementById('saveStudentId').value = outputBox.dataset.studentId;
        document.getElementById('saveMeetingNumber').value = outputBox.dataset.meetingNumber;
        document.getElementById('saveReportDate').value = outputBox.dataset.reportDate;
        document.getElementById('saveMateri').value = outputBox.dataset.materi;
        document.getElementById('saveBehavior').value = outputBox.dataset.behavior;
        document.getElementById('saveContent').value = text;

        // Submit the form
        document.getElementById('saveReportForm').submit();
    });
</script>
@endsection
