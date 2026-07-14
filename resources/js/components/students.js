/**
 * Students Component Logic
 */
document.addEventListener('turbo:load', () => {
    const tabMurid = document.getElementById('tab-murid');
    if (!tabMurid) return;

    // ── Subject combobox: Tom Select with create ────────────────
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

    // ── Edit Modal ──────────────────────────────────────────────
    const editModal = document.getElementById('editModal');
    const editForm  = document.getElementById('editForm');
    const editName  = document.getElementById('editName');
    const editMeetingCount = document.getElementById('editMeetingCount');
    const editFirstMeetingDate = document.getElementById('editFirstMeetingDate');

    function openEditModal(id, name, subject, meetingCount, firstMeetingDate) {
        if (!editName || !editMeetingCount || !editForm) return;
        editName.value = name;
        editMeetingCount.value = meetingCount;
        if (editFirstMeetingDate) {
            editFirstMeetingDate.value = firstMeetingDate || '';
        }
        editForm.action = `/students/${id}`;

        // Set the Tom Select value for the edit subject field
        const editSubjectEl = document.getElementById('editSubject');
        if (editSubjectEl) {
            const ts = editSubjectEl._tomSelect || editSubjectEl.tomselect;
            if (ts) {
                // If the subject doesn't exist as an option yet, add it first
                if (!ts.options[subject]) {
                    ts.addOption({ value: subject, text: subject });
                }
                ts.setValue(subject, true); // true = silent (no change event)
            }
        }

        if (editModal) editModal.classList.add('show');
    }
    window.openEditModal = openEditModal;

    function closeEditModal() {
        if (editModal) editModal.classList.remove('show');
    }
    window.closeEditModal = closeEditModal;

    // Close modal if user clicks outside modal-content
    if (editModal) {
        editModal.addEventListener('click', function(e) {
            if (e.target === editModal) {
                closeEditModal();
            }
        });
    }
});
