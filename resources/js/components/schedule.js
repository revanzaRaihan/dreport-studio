/**
 * Schedule Component Logic
 */
document.addEventListener('turbo:load', () => {
    const tabJadwal = document.getElementById('tab-jadwal');
    if (!tabJadwal) return;

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
        if (!editStudentSearch || !editSubjectFilter) return;
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
    window.filterEditStudents = filterEditStudents;

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
        if (!editForm || !editDayOfWeek || !editStartTime || !editEndTime || !editLabel) return;
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
});
