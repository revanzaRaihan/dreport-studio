/**
 * Report Generator Component Logic
 */
document.addEventListener('turbo:load', () => {
    const tabBuat = document.getElementById('tab-buat');
    if (!tabBuat) return;

    // Load data from DOM attributes
    const meetingNumbers = JSON.parse(tabBuat.dataset.meetingNumbers || '{}');
    const pendingReports = JSON.parse(tabBuat.dataset.pendingReports || '{}');
    const nextDates = JSON.parse(tabBuat.dataset.nextDates || '{}');
    
    const translateNoMatch = tabBuat.dataset.translateNoMatch || 'Tidak ada murid yang cocok.';
    const translatePlaceholder = tabBuat.dataset.translatePlaceholder || 'Pilih murid…';
    const translateMeeting = tabBuat.dataset.translateMeeting || 'Meeting ke-';
    const translateSkip = tabBuat.dataset.translateSkip || 'Lewati';
    const translateCreateFree = tabBuat.dataset.translateCreateFree || 'Buat Sesi Bebas Baru';

    let pickerInstance; 
    let selectedPendingReportId = null;
    let tomSelectInstance; 

    const selectStudent = document.getElementById('genStudent');
    const inputMeeting  = document.getElementById('genMeeting');
    const btnGenerate   = document.getElementById('btnGenerate');
    const btnRegenerate = document.getElementById('btnRegenerate');
    const btnCopy       = document.getElementById('btnCopy');
    const btnSave       = document.getElementById('btnSave');
    const statusLine    = document.getElementById('genStatus');
    const outputCard    = document.getElementById('outputCard');
    const outputBox     = document.getElementById('outputBox');
    
    const pendingSelect = document.getElementById('genPendingReport');
    const pendingWrapper = document.getElementById('pendingReportWrapper');

    // ── Student picker — clean input-style Tom Select ────────────
    if (typeof TomSelect !== 'undefined' && selectStudent) {
        tomSelectInstance = new TomSelect(selectStudent, {
            create: false,
            maxItems: 1,
            openOnFocus: true,
            selectOnTab: true,
            placeholder: translatePlaceholder,
            render: {
                no_results: () => `<div style="padding:10px 14px;color:var(--muted);">${translateNoMatch}</div>`,
            },
            onChange(studentId) {
                const opt = this.options[studentId];
                this.control_input.placeholder = opt ? opt.text : translatePlaceholder;
                this.control_input.value = '';

                if (studentId && meetingNumbers[studentId] !== undefined) {
                    inputMeeting.value = meetingNumbers[studentId];
                } else {
                    inputMeeting.value = '';
                }

                if (studentId && nextDates[studentId] !== undefined) {
                    setDateValue(nextDates[studentId]);
                } else {
                    const today = new Date();
                    const yyyy = today.getFullYear();
                    const mm = String(today.getMonth() + 1).padStart(2, '0');
                    const dd = String(today.getDate()).padStart(2, '0');
                    setDateValue(`${yyyy}-${mm}-${dd}`);
                }

                selectStudent.dispatchEvent(new Event('change', { bubbles: true }));
                this.blur();
            }
        });
    }

    // ── Datepicker Instantiation ────────────────────────────────
    function setDateValue(dateString) {
        const genDate = document.getElementById('genDate');
        const genDateDisplay = document.getElementById('genDateDisplay');
        if (!genDate || !genDateDisplay) return;
        genDate.value = dateString;

        if (dateString) {
            const localeStr = document.documentElement.lang === 'en' ? 'en-US' : 'id-ID';
            const formatted = new Date(dateString).toLocaleDateString(localeStr, {
                day: '2-digit',
                month: 'long',
                year: 'numeric'
            });
            genDateDisplay.value = formatted;

            if (pickerInstance) {
                const parts = dateString.split('-');
                if (parts.length === 3) {
                    pickerInstance.setDate(new Date(parts[0], parts[1] - 1, parts[2]));
                }
            }
        } else {
            genDateDisplay.value = '';
        }
    }

    const initialDateEl = document.getElementById('genDate');
    if (initialDateEl) {
        setDateValue(initialDateEl.value);
    }

    if (typeof MCDatepicker !== 'undefined' && document.getElementById('genDateDisplay')) {
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
    }

    // ── Parse Query Parameters for Auto-selection ────────────────
    const urlParams = new URLSearchParams(window.location.search);
    const urlStudentId = urlParams.get('student_id');
    const urlPendingReportId = urlParams.get('pending_report_id');

    if (urlStudentId) {
        setTimeout(() => {
            if (tomSelectInstance) {
                tomSelectInstance.setValue(urlStudentId);
                
                if (urlPendingReportId) {
                    setTimeout(() => {
                        if (pendingSelect) {
                            pendingSelect.value = urlPendingReportId;
                            pendingSelect.dispatchEvent(new Event('change', { bubbles: true }));
                        }
                    }, 100);
                }
            }
        }, 50);
    }

    // ── Pending Reports Filter Logic ──────────────────────────────
    if (selectStudent && pendingSelect) {
        selectStudent.addEventListener('change', function() {
            const studentId = this.value;
            pendingSelect.innerHTML = '';
            selectedPendingReportId = null;

            if (!studentId) {
                pendingWrapper.style.display = 'none';
                return;
            }

            const filtered = pendingReports[studentId] || [];

            if (filtered.length > 0) {
                let html = `<option value="">-- ${translateSkip} (${translateCreateFree}) --</option>`;
                filtered.forEach(r => {
                    const localeStr = document.documentElement.lang === 'en' ? 'en-US' : 'id-ID';
                    const formattedDate = new Date(r.report_date).toLocaleDateString(localeStr, {
                        day: '2-digit',
                        month: 'long',
                        year: 'numeric'
                    });
                    html += `<option value="${r.id}" data-meeting="${r.meeting_number}" data-date="${r.report_date}">${translateMeeting}${r.meeting_number} (${formattedDate})</option>`;
                });
                pendingSelect.innerHTML = html;
                pendingWrapper.style.display = 'block';
            } else {
                pendingWrapper.style.display = 'none';
            }
        });
    }

    // Auto-update date and meeting number when pending report is chosen
    if (pendingSelect) {
        pendingSelect.addEventListener('change', function() {
            if (this.value) {
                selectedPendingReportId = this.value;
                const selectedOpt = this.options[this.selectedIndex];
                const meetingNum = selectedOpt.dataset.meeting;
                const reportDate = selectedOpt.dataset.date;
                
                inputMeeting.value = meetingNum;
                setDateValue(reportDate);
            } else {
                selectedPendingReportId = null;
                const studentId = selectStudent.value;
                if (studentId && meetingNumbers[studentId] !== undefined) {
                    inputMeeting.value = meetingNumbers[studentId];
                } else {
                    inputMeeting.value = '';
                }
                if (studentId && nextDates[studentId] !== undefined) {
                    setDateValue(nextDates[studentId]);
                } else {
                    const today = new Date();
                    const yyyy = today.getFullYear();
                    const mm = String(today.getMonth() + 1).padStart(2, '0');
                    const dd = String(today.getDate()).padStart(2, '0');
                    setDateValue(`${yyyy}-${mm}-${dd}`);
                }
            }
        });
    }

    // ── Drag & Drop Photo Upload ────────────────────────────────
    const dropzone = document.getElementById('dropzone');
    const reportImage = document.getElementById('reportImage');
    const uploadFilename = document.getElementById('uploadFilename');
    
    if (dropzone && reportImage) {
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
    }

    let generating = false;

    // ── Generate report logic ──────────────────────────────────
    async function generateReport() {
        if (generating) return;
        generating = true;

        const studentId = selectStudent.value;
        const reportDate = document.getElementById('genDate').value;
        const meetingNumber = inputMeeting.value.trim();
        const materi = document.getElementById('genMateri').value.trim();
        const behavior = document.getElementById('genBehavior').value.trim();

        // Validation
        if (!studentId) {
            showStatus('Silakan pilih murid terlebih dahulu.', 'err');
            generating = false;
            return;
        }
        if (!materi || !behavior) {
            showStatus('Materi dan behavior harus diisi.', 'err');
            generating = false;
            return;
        }
        if (!meetingNumber || isNaN(meetingNumber)) {
            showStatus('Nomor meeting harus diisi dengan angka.', 'err');
            generating = false;
            return;
        }

        // Loading UI state
        btnGenerate.disabled = true;
        btnGenerate.classList.add('loading');
        if (btnRegenerate) btnRegenerate.disabled = true;
        showStatus('Sedang men-generate laporan...', '');

        try {
            const generateRoute = tabBuat.dataset.generateRoute;
            const data = await window.api.post(generateRoute, {
                student_id: studentId,
                report_date: reportDate,
                meeting_number: meetingNumber,
                materi: materi,
                behavior: behavior,
                language: document.getElementById('genLanguage').value,
                report_type: document.getElementById('genReportType') ? document.getElementById('genReportType').value : 'full'
            });

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
            if (data.warning) {
                showStatus(data.warning, 'err');
            } else {
                showStatus('Berhasil di-generate. Cek & edit jika perlu sebelum disimpan.', 'ok');
            }
            
            // Scroll output card into view
            outputCard.scrollIntoView({ behavior: 'smooth', block: 'nearest' });

        } catch (error) {
            showStatus(error.message, 'err');
        } finally {
            generating = false;
            btnGenerate.disabled = false;
            btnGenerate.classList.remove('loading');
            if (btnRegenerate) btnRegenerate.disabled = false;
        }
    }

    if (btnGenerate) btnGenerate.addEventListener('click', generateReport);
    if (btnRegenerate) btnRegenerate.addEventListener('click', generateReport);

    // Report Type selection toggles
    const btnTypeFull = document.getElementById('btnTypeFull');
    const btnTypeOverview = document.getElementById('btnTypeOverview');
    const inputReportType = document.getElementById('genReportType');

    if (btnTypeFull && btnTypeOverview && inputReportType) {
        btnTypeFull.addEventListener('click', () => {
            inputReportType.value = 'full';
            btnTypeFull.classList.add('active');
            btnTypeFull.style.background = 'var(--teal)';
            btnTypeFull.style.color = 'var(--paper)';
            btnTypeOverview.classList.remove('active');
            btnTypeOverview.style.background = 'transparent';
            btnTypeOverview.style.color = 'var(--muted)';
        });

        btnTypeOverview.addEventListener('click', () => {
            inputReportType.value = 'overview';
            btnTypeOverview.classList.add('active');
            btnTypeOverview.style.background = 'var(--teal)';
            btnTypeOverview.style.color = 'var(--paper)';
            btnTypeFull.classList.remove('active');
            btnTypeFull.style.background = 'transparent';
            btnTypeFull.style.color = 'var(--muted)';
        });
    }

    // Helpers
    function showStatus(msg, type) {
        if (!statusLine) return;
        statusLine.textContent = msg;
        statusLine.className = 'status-line';
        if (type === 'err') statusLine.classList.add('err');
        if (type === 'ok') statusLine.classList.add('ok');
    }

    // Copy to clipboard
    if (btnCopy) {
        btnCopy.addEventListener('click', function() {
            const text = outputBox.textContent;
            navigator.clipboard.writeText(text)
                .then(() => window.showToast('Disalin ke clipboard'))
                .catch(() => window.showToast('Gagal menyalin. Silakan copy manual.'));
        });
    }

    // Save report to database
    if (btnSave) {
        btnSave.addEventListener('click', function() {
            const text = outputBox.textContent.trim();
            if (!text) {
                window.showToast('Konten laporan kosong.');
                return;
            }

            const form = document.getElementById('saveReportForm');
            if (!form) return;

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
    }
});
