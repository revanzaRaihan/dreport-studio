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
            }
        });

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

    // Expose setFilter globally
    window.setFilter = setFilter;
});
