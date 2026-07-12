@extends('layouts.app')

@section('page_title', 'Buat Laporan Baru')
@section('page_description', 'Generate draf laporan belajar murid secara otomatis menggunakan AI.')

@section('styles')
<style>
    /* Premium Grid Layout for Form */
    .report-grid {
        display: grid;
        grid-template-columns: 1fr;
        gap: 24px;
        margin-top: 16px;
    }
    
    @media (min-width: 768px) {
        .report-grid {
            grid-template-columns: 1.05fr 0.95fr;
            gap: 32px;
        }
    }

    /* Drag and Drop Upload Box */
    .upload-dropzone {
        border: 2px dashed var(--line, #E4DCCE);
        border-radius: 10px;
        padding: 24px 16px;
        text-align: center;
        background: #FCFAF6;
        cursor: pointer;
        transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        gap: 10px;
        position: relative;
        min-height: 120px;
        box-sizing: border-box;
    }
    
    .upload-dropzone:hover, .upload-dropzone.dragover {
        border-color: var(--teal, #2F8F7E);
        background: #F4FAF8;
        box-shadow: 0 4px 12px rgba(47, 143, 126, 0.04);
    }
    
    .upload-dropzone.has-file {
        border-style: solid;
        border-color: var(--teal, #2F8F7E);
        background: #F4FAF8;
    }

    .upload-icon {
        color: var(--muted, #8E8370);
        transition: transform 0.2s ease, color 0.2s ease;
    }
    
    .upload-dropzone:hover .upload-icon {
        color: var(--teal, #2F8F7E);
        transform: translateY(-2px);
    }
    
    .browse-link {
        color: var(--teal, #2F8F7E);
        font-weight: 600;
        text-decoration: underline;
    }
    
    .upload-filename {
        display: none;
        font-size: 12px;
        font-weight: 600;
        color: var(--teal, #2F8F7E);
        background: #EBF8F6;
        padding: 6px 12px;
        border-radius: 6px;
        border: 1px solid #C4EDE5;
        max-width: 90%;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }
</style>
@endsection

@section('content')
<section class="panel active" id="tab-buat">
    @if($datasetCount === 0)
        <div class="alert alert-danger" style="margin-bottom: 20px;">
            <strong>Peringatan:</strong> Kamu belum menambahkan contoh laporan di tab <strong>Dataset Gaya</strong>. AI membutuhkan setidaknya 1 contoh laporan lama milikmu sebagai referensi few-shot agar hasilnya mirip gaya tulisanmu.
        </div>
    @endif

    <div class="card" style="padding: 24px;">
        <h2 style="margin-bottom: 4px;">Laporan baru</h2>
        <p class="desc" style="margin-bottom: 20px;">Pilih murid, isi materi &amp; behavior. Tanggal dan nomor meeting terisi otomatis dari data tracking.</p>

        <div class="report-grid">
            <!-- Kolom Kiri: Metadata Sesi -->
            <div style="display: flex; flex-direction: column; gap: 16px;">
                <div class="form-group" style="margin-bottom: 0;">
                    <label for="genStudent" style="font-weight: 600;">Murid</label>
                    <select id="genStudent" class="no-tom-select student-picker">
                        <option value=""></option>
                        @foreach($students as $student)
                            <option value="{{ $student->id }}">{{ $student->name }} — {{ $student->subject }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="form-group" id="pendingReportWrapper" style="display: none; margin-bottom: 0; background: #EBF8F6; border: 1px solid #C4EDE5; padding: 12px; border-radius: 8px;">
                    <label for="genPendingReport" style="color: var(--teal, #2F8F7E); font-weight: 700; font-size: 12.5px; margin-bottom: 6px; display: block;">Listing Daily Report Terdeteksi:</label>
                    <select id="genPendingReport" class="no-tom-select" style="background: #ffffff; border: 1.5px solid var(--line); border-radius: 6px; padding: 8px 10px; font-size: 13.5px; font-family: inherit; color: var(--ink); cursor: pointer; width: 100%;">
                        <!-- Will be dynamically populated via JS -->
                    </select>
                    <span style="font-size: 11px; color: var(--muted); display: block; margin-top: 4px;">Pilih sesi yang tertunda di atas untuk mengaktifkan sinkronisasi otomatis.</span>
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 14px;">
                    <div id="datepickerWrapper" style="position: relative;">
                        <label for="genDateDisplay" style="font-weight: 600;">Tanggal</label>
                        <input type="text" id="genDateDisplay" readonly style="cursor: pointer; background: #FCFAF6; margin: 0;" placeholder="Pilih Tanggal...">
                        <input type="hidden" id="genDate" value="{{ date('Y-m-d') }}">
                    </div>
                    <div>
                        <label for="genMeeting" style="font-weight: 600;">Meeting ke-</label>
                        <input type="text" id="genMeeting" placeholder="mis. 5" autocomplete="off" style="margin: 0;">
                    </div>
                </div>

                <div>
                    <label for="genLanguage" style="font-weight: 600;">Bahasa Laporan</label>
                    <select id="genLanguage" class="no-tom-select" style="background: #FCFAF6; border: 1.5px solid var(--line); border-radius: 8px; padding: 10px 12px; font-size: 14px; font-family: inherit; color: var(--ink); cursor: pointer; width: 100%; box-sizing: border-box; height: 42px; margin: 0;">
                        <option value="id" selected>Bahasa Indonesia</option>
                        <option value="en">English (Inggris)</option>
                    </select>
                </div>
            </div>

            <!-- Kolom Ranan: Detail Pembelajaran -->
            <div style="display: flex; flex-direction: column; gap: 16px;">
                <div class="form-group" style="margin-bottom: 0;">
                    <label for="genMateri" style="font-weight: 600;">Materi</label>
                    <input type="text" id="genMateri" placeholder="mis. For Loop, penutupan array method, dst." autocomplete="off" style="margin: 0;">
                </div>

                <div class="form-group" style="margin-bottom: 0;">
                    <label for="genBehavior" style="font-weight: 600;">Behavior murid</label>
                    <textarea id="genBehavior" placeholder="mis. bisa menjelaskan kembali, agak lambat di bagian logika kondisional, aktif bertanya" autocomplete="off" style="margin: 0; min-height: 84px; resize: vertical;"></textarea>
                </div>

                <div class="form-group" style="margin-bottom: 0;">
                    <label style="font-weight: 600; display: block; margin-bottom: 6px;">Foto Dokumentasi (Opsional)</label>
                    <div class="upload-dropzone" id="dropzone">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="upload-icon">
                            <rect width="18" height="18" x="3" y="3" rx="2" ry="2"/><circle cx="9" cy="9" r="2"/><path d="m21 15-3.086-3.086a2 2 0 0 0-2.828 0L6 21"/>
                        </svg>
                        <div class="upload-text" style="font-size: 12.5px; color: var(--muted);">
                            Drag & drop foto atau <span class="browse-link">klik untuk memilih</span>
                        </div>
                        <div class="upload-filename" id="uploadFilename"></div>
                    </div>
                </div>
            </div>
        </div>

        <div style="margin-top: 24px; display: flex; align-items: center; justify-content: space-between; border-top: 1.5px solid var(--line); padding-top: 20px; flex-wrap: wrap; gap: 12px;">
            <button class="btn" id="btnGenerate" {{ $datasetCount === 0 ? 'disabled' : '' }} style="margin: 0;">
                <span class="btn-spinner spinner"></span>
                <span class="btn-text">Generate laporan</span>
            </button>
            <div class="status-line" id="genStatus" style="margin: 0; flex-grow: 1; text-align: right;"></div>
        </div>
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
<form id="saveReportForm" action="{{ route('report.store') }}" method="POST" style="display: none;" enctype="multipart/form-data">
    @csrf
    <input type="hidden" name="student_id" id="saveStudentId">
    <input type="hidden" name="meeting_number" id="saveMeetingNumber">
    <input type="hidden" name="report_date" id="saveReportDate">
    <input type="hidden" name="materi" id="saveMateri">
    <input type="hidden" name="behavior" id="saveBehavior">
    <input type="hidden" name="content" id="saveContent">
    <input type="hidden" name="pending_report_id" id="savePendingReportId">
    <input type="file" id="reportImage" accept="image/*" name="image" class="no-tom-select">
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
    });

    // ── Datepicker Instantiation ────────────────────────────────
    function setDateValue(dateString) {
        const genDate = document.getElementById('genDate');
        const genDateDisplay = document.getElementById('genDateDisplay');
        genDate.value = dateString;

        if (dateString) {
            const formatted = new Date(dateString).toLocaleDateString('id-ID', {
                day: '2-digit',
                month: 'long',
                year: 'numeric'
            });
            genDateDisplay.value = formatted;
        } else {
            genDateDisplay.value = '';
        }
    }

    document.addEventListener('DOMContentLoaded', () => {
        // Set initial today date
        setDateValue(document.getElementById('genDate').value);

        pickerInstance = MCDatepicker.create({
            el: '#genDateDisplay',
            bodyType: 'inline',
            dateFormat: 'YYYY-MM-DD',
            customWeekDays: ['Min', 'Sen', 'Sel', 'Rab', 'Kam', 'Jum', 'Sab'],
            customMonths: ['Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'],
        });

        pickerInstance.onSelect((date, formatedDate) => {
            setDateValue(formatedDate);
        });
    });

    // ── Pending Reports Filter Logic ──────────────────────────────
    const pendingSelect = document.getElementById('genPendingReport');
    const pendingWrapper = document.getElementById('pendingReportWrapper');

    selectStudent.addEventListener('change', function() {
        const studentId = this.value;
        pendingSelect.innerHTML = '';
        selectedPendingReportId = null;

        if (!studentId) {
            pendingWrapper.style.display = 'none';
            return;
        }

        // Filter pending reports for the chosen student
        const filtered = pendingReports.filter(r => r.student_id === studentId);

        if (filtered.length > 0) {
            // Populate select dropdown
            let html = '<option value="">-- Lewati (Buat Sesi Bebas Baru) --</option>';
            filtered.forEach(r => {
                const formattedDate = new Date(r.report_date).toLocaleDateString('id-ID', {
                    day: '2-digit',
                    month: 'long',
                    year: 'numeric'
                });
                html += `<option value="${r.id}" data-meeting="${r.meeting_number}" data-date="${r.report_date}">Meeting ${r.meeting_number} (${formattedDate})</option>`;
            });
            pendingSelect.innerHTML = html;
            pendingWrapper.style.display = 'block';
        } else {
            pendingWrapper.style.display = 'none';
        }
    });

    // Auto-update date and meeting number when pending report is chosen
    pendingSelect.addEventListener('change', function() {
        if (this.value) {
            selectedPendingReportId = this.value;
            const selectedOpt = this.options[this.selectedIndex];
            const meetingNum = selectedOpt.dataset.meeting;
            const reportDate = selectedOpt.dataset.date;
            
            inputMeeting.value = meetingNum;
            setDateValue(reportDate);
        }
    });

    // ── Drag & Drop Photo Upload ────────────────────────────────
    const dropzone = document.getElementById('dropzone');
    const reportImage = document.getElementById('reportImage');
    const uploadFilename = document.getElementById('uploadFilename');
    const uploadText = dropzone.querySelector('.upload-text');

    dropzone.addEventListener('click', () => reportImage.click());

    dropzone.addEventListener('dragover', (e) => {
        e.preventDefault();
        dropzone.classList.add('dragover');
    });

    dropzone.addEventListener('dragleave', () => {
        dropzone.classList.remove('dragover');
    });

    dropzone.addEventListener('drop', (e) => {
        e.preventDefault();
        dropzone.classList.remove('dragover');
        if (e.dataTransfer.files.length > 0) {
            reportImage.files = e.dataTransfer.files;
            updateFilenameDisplay();
        }
    });

    reportImage.addEventListener('change', () => {
        updateFilenameDisplay();
    });

    function updateFilenameDisplay() {
        if (reportImage.files.length > 0) {
            const file = reportImage.files[0];
            uploadFilename.textContent = file.name;
            uploadFilename.style.display = 'block';
            uploadText.style.display = 'none';
            dropzone.classList.add('has-file');
        } else {
            uploadFilename.style.display = 'none';
            uploadText.style.display = 'block';
            dropzone.classList.remove('has-file');
        }
    }

    // ── Generate report logic ──────────────────────────────────
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
                    behavior: behavior,
                    language: document.getElementById('genLanguage').value
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

        // Submit the form
        form.submit();
    });
</script>
@endsection
