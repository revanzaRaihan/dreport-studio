@extends('layouts.app')

@section('page_title', __('Buat Laporan Baru'))
@section('page_description', __('Generate draf laporan belajar murid secara otomatis menggunakan AI.'))

@section('content')
<section class="panel active" id="tab-buat"
    data-meeting-numbers="{{ json_encode($meetingNumbers) }}"
    data-pending-reports="{{ json_encode($pendingReports) }}"
    data-next-dates="{{ json_encode($nextDates) }}"
    data-translate-no-match="{{ __('Tidak ada murid yang cocok.') }}"
    data-translate-placeholder="{{ __('Pilih murid…') }}"
    data-translate-meeting="{{ __('Meeting ke-') }}"
    data-translate-skip="{{ __('Lewati') }}"
    data-translate-create-free="{{ __('Buat Sesi Bebas Baru') }}"
    data-generate-route="{{ route('report.generate') }}">
    @if($datasetCount === 0)
        <div class="alert alert-danger" style="margin-bottom: 20px;">
            {!! __('<strong>Peringatan:</strong> Kamu belum menambahkan contoh laporan di tab <strong>Dataset Gaya</strong>. AI membutuhkan setidaknya 1 contoh laporan lama milikmu sebagai referensi few-shot agar hasilnya mirip gaya tulisanmu.') !!}
        </div>
    @endif

    <div class="card" style="padding: 24px;">
        <h2 style="margin-bottom: 4px;">{{ __('Laporan baru') }}</h2>
        <p class="desc" style="margin-bottom: 20px;">{{ __('Pilih murid, isi materi & behavior. Tanggal dan nomor meeting terisi otomatis dari data tracking.') }}</p>

        <div class="report-grid">
            <!-- Kolom Kiri: Metadata Sesi -->
            <div style="display: flex; flex-direction: column; gap: 16px;">
                <div class="form-group" style="margin-bottom: 0;">
                    <label for="genStudent" style="font-weight: 600;">{{ __('Murid') }}</label>
                    <select id="genStudent" class="no-tom-select student-picker">
                        <option value=""></option>
                        @foreach($students as $student)
                            <option value="{{ $student->id }}">{{ $student->name }} — {{ $student->subject }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="form-group" id="pendingReportWrapper" style="display: none; margin-bottom: 0; background: #EBF8F6; border: 1px solid #C4EDE5; padding: 12px; border-radius: 8px;">
                    <label for="genPendingReport" style="color: var(--teal, #2F8F7E); font-weight: 700; font-size: 12.5px; margin-bottom: 6px; display: block;">{{ __('Listing Daily Report Terdeteksi:') }}</label>
                    <select id="genPendingReport" class="no-tom-select" style="background: #ffffff; border: 1.5px solid var(--line); border-radius: 6px; padding: 8px 10px; font-size: 13.5px; font-family: inherit; color: var(--ink); cursor: pointer; width: 100%;">
                        <!-- Will be dynamically populated via JS -->
                    </select>
                    <span style="font-size: 11px; color: var(--muted); display: block; margin-top: 4px;">{{ __('Pilih sesi yang tertunda di atas untuk mengaktifkan sinkronisasi otomatis.') }}</span>
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 14px;">
                    <div id="datepickerWrapper" style="position: relative;">
                        <label for="genDateDisplay" style="font-weight: 600;">{{ __('Tanggal') }}</label>
                        <input type="text" id="genDateDisplay" readonly style="cursor: pointer; background: #FCFAF6; margin: 0;" placeholder="{{ __('Pilih Tanggal...') }}">
                        <input type="hidden" id="genDate" value="{{ date('Y-m-d') }}">
                    </div>
                    <div>
                        <label for="genMeeting" style="font-weight: 600;">{{ __('Meeting ke-') }}</label>
                        <input type="text" id="genMeeting" placeholder="mis. 5" autocomplete="off" style="margin: 0;">
                    </div>
                </div>

                <div>
                    <label for="genLanguage" style="font-weight: 600;">{{ __('Bahasa Laporan') }}</label>
                    <select id="genLanguage" class="no-tom-select" style="background: #FCFAF6; border: 1.5px solid var(--line); border-radius: 8px; padding: 10px 12px; font-size: 14px; font-family: inherit; color: var(--ink); cursor: pointer; width: 100%; box-sizing: border-box; height: 42px; margin: 0;">
                        <option value="id" selected>{{ __('Bahasa Indonesia') }}</option>
                        <option value="en">{{ __('Bahasa Inggris') }}</option>
                    </select>
                </div>
            </div>

            <!-- Kolom Ranan: Detail Pembelajaran -->
            <div style="display: flex; flex-direction: column; gap: 16px;">
                <div class="form-group" style="margin-bottom: 0;">
                    <label for="genMateri" style="font-weight: 600;">{{ __('Materi') }}</label>
                    <input type="text" id="genMateri" placeholder="{{ __('mis. For Loop, penutupan array method, dst.') }}" autocomplete="off" style="margin: 0;">
                </div>

                <div class="form-group" style="margin-bottom: 0;">
                    <label for="genBehavior" style="font-weight: 600;">{{ __('Behavior murid') }}</label>
                    <textarea id="genBehavior" placeholder="{{ __('mis. bisa menjelaskan kembali, agak lambat di bagian logika kondisional, aktif bertanya') }}" autocomplete="off" style="margin: 0; min-height: 84px; resize: vertical;"></textarea>
                </div>

                <div class="form-group" style="margin-bottom: 0;">
                    <label style="font-weight: 600; display: block; margin-bottom: 6px;">{{ __('Foto Dokumentasi (Opsional)') }}</label>
                    <div class="upload-dropzone" id="dropzone">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="upload-icon">
                            <rect width="18" height="18" x="3" y="3" rx="2" ry="2"/><circle cx="9" cy="9" r="2"/><path d="m21 15-3.086-3.086a2 2 0 0 0-2.828 0L6 21"/>
                        </svg>
                        <div class="upload-text" style="font-size: 12.5px; color: var(--muted);">
                            {{ __('Drag & drop foto atau') }} <span class="browse-link">{{ __('klik untuk memilih') }}</span>
                        </div>
                        <div class="upload-filename" id="uploadFilename"></div>
                    </div>
                </div>
            </div>
        </div>

        <div style="margin-top: 24px; display: flex; align-items: center; justify-content: space-between; border-top: 1.5px solid var(--line); padding-top: 20px; flex-wrap: wrap; gap: 12px;">
            <button class="btn" id="btnGenerate" {{ $datasetCount === 0 ? 'disabled' : '' }} style="margin: 0;">
                <span class="btn-spinner spinner"></span>
                <span class="btn-text">{{ __('Generate laporan') }}</span>
            </button>
            <div class="status-line" id="genStatus" style="margin: 0; flex-grow: 1; text-align: right;"></div>
        </div>
    </div>

    <!-- Output Card (Hasil) -->
    <div class="card" id="outputCard" style="display:none;">
        <h2>{{ __('Hasil') }}</h2>
        <p class="desc">{{ __('Kamu bisa mengedit teks di bawah secara langsung sebelum menyimpannya.') }}</p>
        <div class="output-box" id="outputBox" contenteditable="true"></div>
        
        <div class="actions-row">
            <button class="btn secondary" id="btnCopy">{{ __('Copy ke clipboard') }}</button>
            <button class="btn" id="btnSave">{{ __('Simpan ke riwayat') }}</button>
            <button class="btn secondary" id="btnRegenerate">{{ __('Generate ulang') }}</button>
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


