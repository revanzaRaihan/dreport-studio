/**
 * History Component Logic
 */
document.addEventListener('turbo:load', () => {
    const tabRiwayatMurid = document.getElementById('tab-riwayat-murid');
    if (!tabRiwayatMurid) return;

    const adminWaNumber = tabRiwayatMurid.dataset.adminWaNumber || '';

    function copyText(text) {
        navigator.clipboard.writeText(text)
            .then(() => window.showToast('Disalin ke clipboard'))
            .catch(() => window.showToast('Gagal menyalin. Silakan copy manual.'));
    }
    window.copyText = copyText;

    document.querySelectorAll('.btn-wa').forEach(btn => {
        btn.addEventListener('click', function() {
            const content = this.dataset.content;

            // Format message: langsung kirim teks laporan asli tanpa format tambahan
            let message = content;
            const encodedText = encodeURIComponent(message);
            
            let waUrl = `whatsapp://send?text=${encodedText}`;
            if (adminWaNumber) {
                const cleanPhone = adminWaNumber.replace(/\D/g, '');
                waUrl = `whatsapp://send?phone=${cleanPhone}&text=${encodedText}`;
            }

            window.open(waUrl, '_blank');
        });
    });

    // --- Edit Modal Controls ---
    const editModal = document.getElementById('editReportModal');
    const editForm = document.getElementById('editReportForm');
    const editMeetingNumber = document.getElementById('editMeetingNumber');
    const editReportDate = document.getElementById('editReportDate');
    const editMateri = document.getElementById('editMateri');
    const editBehavior = document.getElementById('editBehavior');
    const editContent = document.getElementById('editContent');
    const editImagePreviewWrapper = document.getElementById('editImagePreviewWrapper');
    const editImagePreview = document.getElementById('editImagePreview');

    function openEditModal(id, meeting, date, materi, behavior, content, imageUrl) {
        if (!editForm || !editMeetingNumber || !editReportDate || !editMateri || !editBehavior || !editContent) return;
        editForm.action = `/reports/${id}`;
        editMeetingNumber.value = meeting;
        editReportDate.value = date;
        editMateri.value = materi;
        editBehavior.value = behavior;
        editContent.value = content;

        if (editImagePreview && editImagePreviewWrapper) {
            if (imageUrl && imageUrl !== 'null' && imageUrl !== '') {
                editImagePreview.src = imageUrl;
                editImagePreviewWrapper.style.display = 'block';
            } else {
                editImagePreview.src = '';
                editImagePreviewWrapper.style.display = 'none';
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

    document.querySelectorAll('.btn-edit').forEach(btn => {
        btn.addEventListener('click', function() {
            const id = this.dataset.id;
            const meeting = this.dataset.meeting;
            const date = this.dataset.date;
            const materi = this.dataset.materi;
            const behavior = this.dataset.behavior;
            const content = this.dataset.content;
            const image = this.dataset.image;

            openEditModal(id, meeting, date, materi, behavior, content, image);
        });
    });
});
