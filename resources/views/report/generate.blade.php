@extends('layouts.app')

@section('page_title', 'Buat Laporan Baru')
@section('page_description', 'Generate draf laporan belajar murid secara otomatis menggunakan AI.')

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

        <div class="form-group" id="pendingReportWrapper" style="display: none; margin-top: 14px; margin-bottom: 14px;">
            <label for="genPendingReport" style="color: var(--teal, #2F8F7E); font-weight: 600;">Listing Daily Report (Belum Dibuat)</label>
            <select id="genPendingReport" class="no-tom-select" style="background: #FCFAF6; border: 1.5px solid var(--line, #E4DCCE); border-radius: 8px; padding: 10px 12px; font-size: 14px; font-family: inherit; color: var(--ink, #1B2A41); cursor: pointer; width: 100%;">
                <!-- Will be dynamically populated via JS -->
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

        <div class="form-group">
            <label for="reportImage">Foto Pertemuan (Opsional)</label>
            <input type="file" id="reportImage" accept="image/*" class="no-tom-select" style="background: #FCFAF6; border: 1.5px solid var(--line, #E4DCCE); border-radius: 8px; padding: 10px 12px; font-size: 14px; font-family: inherit; color: var(--ink, #1B2A41); cursor: pointer; width: 100%;">
            <p class="desc" style="margin-top: 4px; font-size: 11.5px;">Foto dokumentasi akan disimpan di Supabase Storage / lokal dan ditampilkan di riwayat.</p>
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
    <input type="hidden" name="pending_report_id" id="savePendingReportId">
</form>
@endsection

@section('scripts')
<script>
    // Meeting numbers mapping (from PHP to JS)
    const meetingNumbers = @json($meetingNumbers);
    // Pending reports mapping (from PHP to JS)
    const pendingReports = @json($pendingReports);

    let pickerInstance; // Global reference for the MCDatepicker
    let selectedPendingReportId = null;

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

        pickerInstance = MCDatepicker.create({
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
        const picker = pickerInstance;

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


    const pendingReportWrapper = document.getElementById('pendingReportWrapper');
    const selectPending = document.getElementById('genPendingReport');

    function formatDateIndo(dateStr) {
        if (!dateStr) return '';
        const months = ['Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
        const parts = dateStr.split('-');
        const dateObj = new Date(parts[0], parts[1] - 1, parts[2]);
        return `${dateObj.getDate()} ${months[dateObj.getMonth()]} ${dateObj.getFullYear()}`;
    }

    function setDateValue(dateStr) {
        const displayInput = document.getElementById('genDateDisplay');
        const hiddenDateInput = document.getElementById('genDate');
        if (!displayInput || !hiddenDateInput) return;
        
        displayInput.value = formatDateIndo(dateStr);
        hiddenDateInput.value = dateStr;

        if (pickerInstance && dateStr) {
            const parts = dateStr.split('-');
            const dateObj = new Date(parts[0], parts[1] - 1, parts[2]);
            pickerInstance.setDate(dateObj);
        }
    }

    function getTodayStr() {
        const today = new Date();
        const yyyy = today.getFullYear();
        const mm = String(today.getMonth() + 1).padStart(2, '0');
        const dd = String(today.getDate()).padStart(2, '0');
        return `${yyyy}-${mm}-${dd}`;
    }

    // Auto-update meeting field when student is selected (fallback for native change)
    selectStudent.addEventListener('change', function() {
        const studentId = this.value;
        selectedPendingReportId = null; // reset selected pending report ID

        if (studentId && pendingReports[studentId] && pendingReports[studentId].length > 0) {
            // Show pending report section
            pendingReportWrapper.style.display = 'block';
            selectPending.innerHTML = '';

            // Add "Buat Baru" option
            const defaultOpt = document.createElement('option');
            defaultOpt.value = 'new';
            defaultOpt.textContent = `Buat Baru (Pertemuan ke-${meetingNumbers[studentId] || 1})`;
            selectPending.appendChild(defaultOpt);

            // Add pending reports options
            pendingReports[studentId].forEach(report => {
                const opt = document.createElement('option');
                opt.value = report.id;
                opt.dataset.meeting = report.meeting_number;
                opt.dataset.date = report.report_date;
                opt.textContent = `Pertemuan Ke-${report.meeting_number} (${formatDateIndo(report.report_date)})`;
                selectPending.appendChild(opt);
            });

            // Set to next meeting count by default
            inputMeeting.value = meetingNumbers[studentId] || '';
            setDateValue(getTodayStr());
        } else {
            // Hide pending reports
            pendingReportWrapper.style.display = 'none';
            selectPending.innerHTML = '';
            
            if (studentId && meetingNumbers[studentId] !== undefined) {
                inputMeeting.value = meetingNumbers[studentId];
            } else {
                inputMeeting.value = '';
            }
            setDateValue(getTodayStr());
        }
    });

    // Handle change of pending report selection
    selectPending.addEventListener('change', function() {
        const val = this.value;
        const studentId = selectStudent.value;

        if (val === 'new' || !val) {
            selectedPendingReportId = null;
            inputMeeting.value = meetingNumbers[studentId] || '';
            setDateValue(getTodayStr());
        } else {
            selectedPendingReportId = val;
            const selectedOpt = this.options[this.selectedIndex];
            const meetingNum = selectedOpt.dataset.meeting;
            const reportDate = selectedOpt.dataset.date;
            
            inputMeeting.value = meetingNum;
            setDateValue(reportDate);
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
            outputBox.dataset.pendingReportId = selectedPendingReportId || '';

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

        const form = document.getElementById('saveReportForm');

        // Map values to hidden form inputs
        document.getElementById('saveStudentId').value = outputBox.dataset.studentId;
        document.getElementById('saveMeetingNumber').value = outputBox.dataset.meetingNumber;
        document.getElementById('saveReportDate').value = outputBox.dataset.reportDate;
        document.getElementById('saveMateri').value = outputBox.dataset.materi;
        document.getElementById('saveBehavior').value = outputBox.dataset.behavior;
        document.getElementById('saveContent').value = text;
        document.getElementById('savePendingReportId').value = outputBox.dataset.pendingReportId || '';

        // Attach image if uploaded
        const imageInput = document.getElementById('reportImage');
        if (imageInput && imageInput.files.length > 0) {
            form.enctype = 'multipart/form-data';
            imageInput.name = 'image';
            form.appendChild(imageInput);
        }

        // Submit the form
        form.submit();
    });
</script>
@endsection
