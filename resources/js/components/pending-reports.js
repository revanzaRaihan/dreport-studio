/**
 * Pending Reports Component Logic
 */
document.addEventListener('turbo:load', () => {
    const tabListingReport = document.getElementById('tab-listing-report');
    if (!tabListingReport) return;

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
});
