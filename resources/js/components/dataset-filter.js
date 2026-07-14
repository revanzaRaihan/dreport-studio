/**
 * Dataset Filter & UI Component Logic
 */
document.addEventListener('turbo:load', () => {
    const tabDataset = document.getElementById('tab-dataset');
    if (!tabDataset) return;

    // Toggle recommendation category select dropdown
    const sectionSelect = document.getElementById('sectionTypeSelect');
    const categoryWrapper = document.getElementById('categoryWrapper');
    if (sectionSelect && categoryWrapper) {
        sectionSelect.addEventListener('change', function() {
            if (this.value === 'training_recommendation') {
                categoryWrapper.style.display = 'block';
            } else {
                categoryWrapper.style.display = 'none';
            }
        });
    }

    // Interactive Filtering States
    let currentFilters = {
        lang: 'all',
        type: 'all',
        cat: 'all'
    };

    function setFilter(key, value) {
        currentFilters[key] = value;
        
        // Update active UI styles for buttons
        const containerClass = key === 'lang' ? '.lang-filters' : (key === 'type' ? '.type-filters' : '.category-filters');
        const buttons = document.querySelectorAll(`${containerClass} .sub-tab-btn`);
        
        buttons.forEach(btn => {
            if (btn.getAttribute('data-filter') === value) {
                btn.style.background = 'var(--teal)';
                btn.style.color = 'white';
                btn.classList.add('active');
            } else {
                btn.style.background = 'transparent';
                btn.style.color = 'var(--muted)';
                btn.classList.remove('active');
            }
        });

        // Hide/show category filter row based on type selection
        const catRow = document.getElementById('categoryFilterRow');
        if (catRow) {
            if (currentFilters.type === 'training_recommendation' || currentFilters.type === 'all') {
                catRow.style.display = 'flex';
            } else {
                catRow.style.display = 'none';
                currentFilters.cat = 'all'; // reset category filter if not applicable
                // Reset category filter buttons visual active state
                document.querySelectorAll('.category-filters .sub-tab-btn').forEach(btn => {
                    if (btn.getAttribute('data-filter') === 'all') {
                        btn.style.background = 'var(--teal)';
                        btn.style.color = 'white';
                        btn.classList.add('active');
                    } else {
                        btn.style.background = 'transparent';
                        btn.style.color = 'var(--muted)';
                        btn.classList.remove('active');
                    }
                });
            }
        }

        applyFilters();
    }

    function applyFilters() {
        const items = document.querySelectorAll('.dataset-item');
        let visibleCount = 0;

        items.forEach(item => {
            let matchLang = (currentFilters.lang === 'all' || item.classList.contains(`lang-${currentFilters.lang}`));
            let matchType = (currentFilters.type === 'all' || item.classList.contains(`type-${currentFilters.type}`));
            
            // For category filter
            let matchCat = true;
            if (currentFilters.cat !== 'all') {
                matchCat = item.classList.contains(`cat-${currentFilters.cat}`);
            }

            if (matchLang && matchType && matchCat) {
                item.style.display = 'flex';
                visibleCount++;
            } else {
                item.style.display = 'none';
                // Uncheck checkboxes for hidden elements
                const cb = item.querySelector('.dataset-select-cb');
                if (cb) cb.checked = false;
            }
        });

        // Update batch selection UI count when filter changes
        updateBatchUI();

        // Handle empty message state
        let tempEmpty = document.querySelector('.temp-empty');
        if (visibleCount === 0) {
            if (!tempEmpty) {
                const empty = document.createElement('div');
                empty.className = 'empty temp-empty';
                empty.textContent = 'Tidak ada contoh laporan yang cocok untuk filter ini.';
                document.getElementById('datasetList').appendChild(empty);
            }
            const defaultEmpty = document.querySelector('.empty:not(.temp-empty)');
            if (defaultEmpty) defaultEmpty.style.display = 'none';
        } else {
            if (tempEmpty) {
                tempEmpty.remove();
            }
            const defaultEmpty = document.querySelector('.empty:not(.temp-empty)');
            if (defaultEmpty) defaultEmpty.style.display = 'block';
        }
    }

    // --- Batch Selection & Delete Logic ---
    const selectAllCb = document.getElementById('selectAllDataset');
    const batchActionContainer = document.getElementById('batchActionContainer');
    const selectedCountSpan = document.getElementById('selectedCount');

    function updateBatchUI() {
        const checkedCbs = document.querySelectorAll('.dataset-select-cb:checked');
        const checkedCount = checkedCbs.length;

        if (checkedCount > 0) {
            if (batchActionContainer) batchActionContainer.style.display = 'flex';
            if (selectedCountSpan) selectedCountSpan.textContent = checkedCount;
        } else {
            if (batchActionContainer) batchActionContainer.style.display = 'none';
        }

        if (selectAllCb) {
            const visibleCbs = Array.from(document.querySelectorAll('.dataset-select-cb')).filter(cb => {
                const item = cb.closest('.dataset-item');
                return item ? item.style.display !== 'none' : true;
            });
            const visibleCheckedCount = visibleCbs.filter(cb => cb.checked).length;
            selectAllCb.checked = (visibleCbs.length > 0 && visibleCheckedCount === visibleCbs.length);
        }
    }

    if (selectAllCb) {
        selectAllCb.addEventListener('change', function() {
            const currentItemCbs = document.querySelectorAll('.dataset-select-cb');
            currentItemCbs.forEach(cb => {
                // Only select visible dataset items matching active filters
                const item = cb.closest('.dataset-item');
                const isVisible = item ? item.style.display !== 'none' : true;
                
                if (isVisible) {
                    cb.checked = selectAllCb.checked;
                } else {
                    cb.checked = false;
                }
            });
            updateBatchUI();
        });
    }

    // Event delegation on datasetList to handle checkbox toggles cleanly
    const datasetList = document.getElementById('datasetList');
    if (datasetList) {
        datasetList.addEventListener('change', function(e) {
            if (e.target.classList.contains('dataset-select-cb')) {
                updateBatchUI();
            }
        });
    }

    function triggerDatasetBatchDelete() {
        const checkedCbs = document.querySelectorAll('.dataset-select-cb:checked');
        const ids = Array.from(checkedCbs).map(cb => cb.value);

        if (ids.length === 0) return;

        // Reuse universal delete modal
        const modal = document.getElementById('deleteModal');
        const form = document.getElementById('deleteModalForm');
        const msgEl = document.getElementById('deleteModalMessage');
        if (!modal || !form || !msgEl) return;

        // Clear any previous batch hidden inputs
        form.querySelectorAll('.batch-id-input').forEach(el => el.remove());

        // Set action route to batch delete for dataset
        form.action = '/dataset/batch-delete';
        
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
        msgEl.textContent = `Apakah Anda yakin ingin menghapus ${ids.length} contoh referensi dari dataset?`;
        
        modal.classList.add('show');
    }
    window.triggerDatasetBatchDelete = triggerDatasetBatchDelete;

    // Expose setFilter globally
    window.setFilter = setFilter;
});
