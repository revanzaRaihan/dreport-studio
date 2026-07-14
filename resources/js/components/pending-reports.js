/**
 * Pending Reports Component Logic
 */
document.addEventListener('turbo:load', () => {
    const tabListingReport = document.getElementById('tab-listing-report');
    if (!tabListingReport) return;

    // --- Modal Edit ---
    const editModal = document.getElementById('editModal');
    const editForm  = document.getElementById('editForm');
    const editStudentId = document.getElementById('editStudentId');
    const editMeetingNumber = document.getElementById('editMeetingNumber');
    const editReportDate = document.getElementById('editReportDate');

    function openEditModal(id, studentId, meetingNumber, reportDate) {
        if (!editMeetingNumber || !editReportDate || !editForm) return;
        editMeetingNumber.value = meetingNumber;
        editReportDate.value = reportDate;
        editForm.action = `/pending-reports/${id}`;

        // Handle Tom Select for student_id dropdown in edit modal
        if (editStudentId) {
            const ts = editStudentId.tomselect;
            if (ts) {
                ts.setValue(studentId, true);
            } else {
                editStudentId.value = studentId;
            }
        }

        if (editModal) editModal.classList.add('show');
    }
    window.openEditModal = openEditModal;

    function closeEditModal() {
        if (editModal) editModal.classList.remove('show');
    }
    window.closeEditModal = closeEditModal;

    if (editModal) {
        editModal.addEventListener('click', function(e) {
            if (e.target === editModal) {
                closeEditModal();
            }
        });
    }

    // --- Batch Selection & Delete Logic ---
    const selectAllCb = document.getElementById('selectAllPending');
    const itemCbs = document.querySelectorAll('.pending-select-cb');
    const batchActionContainer = document.getElementById('batchActionContainer');
    const selectedCountSpan = document.getElementById('selectedCount');

    function updateBatchUI() {
        const checkedCbs = document.querySelectorAll('.pending-select-cb:checked');
        const checkedCount = checkedCbs.length;

        if (checkedCount > 0) {
            if (batchActionContainer) batchActionContainer.style.display = 'flex';
            if (selectedCountSpan) selectedCountSpan.textContent = checkedCount;
        } else {
            if (batchActionContainer) batchActionContainer.style.display = 'none';
        }

        if (selectAllCb) {
            selectAllCb.checked = (checkedCount === itemCbs.length && itemCbs.length > 0);
        }
    }

    if (selectAllCb) {
        selectAllCb.addEventListener('change', function() {
            itemCbs.forEach(cb => {
                // Only select visible checkboxes (matching query)
                const listItem = cb.closest('.list-item');
                const isItemVisible = listItem ? listItem.style.display !== 'none' : true;
                
                if (isItemVisible) {
                    cb.checked = selectAllCb.checked;
                } else {
                    cb.checked = false;
                }
            });
            updateBatchUI();
        });
    }

    itemCbs.forEach(cb => {
        cb.addEventListener('change', updateBatchUI);
    });

    function triggerBatchDelete() {
        const checkedCbs = document.querySelectorAll('.pending-select-cb:checked');
        const ids = Array.from(checkedCbs).map(cb => cb.value);

        if (ids.length === 0) return;

        // Reuse universal delete modal
        const modal = document.getElementById('deleteModal');
        const form = document.getElementById('deleteModalForm');
        const msgEl = document.getElementById('deleteModalMessage');
        if (!modal || !form || !msgEl) return;

        // Clear any previous batch hidden inputs
        form.querySelectorAll('.batch-id-input').forEach(el => el.remove());

        // Set action route to batch delete
        form.action = '/pending-reports/batch-delete';
        
        // Append selected IDs as hidden inputs
        ids.forEach(id => {
            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = 'ids[]';
            input.value = id;
            input.className = 'batch-id-input';
            form.appendChild(input);
        });

        // Set confirmation message
        msgEl.textContent = `Apakah Anda yakin ingin menghapus ${ids.length} antrean laporan yang terpilih?`;
        
        modal.classList.add('show');
    }
    window.triggerBatchDelete = triggerBatchDelete;
});
