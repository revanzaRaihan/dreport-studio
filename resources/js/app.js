import Alpine from 'alpinejs';
import { post } from './utils/api';
import './components/report-generator';
import './components/dataset-filter';
import './components/students';
import './components/schedule';
import './components/pending-reports';
import './components/history';
import './components/settings';

window.Alpine = Alpine;
Alpine.start();

// Expose post API utility
window.api = { post };

// ── Lenis Smooth Scroll ──────────────────────────────────
let lenis;
document.addEventListener('turbo:load', () => {
    if (typeof Lenis !== 'undefined') {
        if (!lenis) {
            lenis = new Lenis();
            function raf(time) {
                lenis.raf(time);
                requestAnimationFrame(raf);
            }
            requestAnimationFrame(raf);
        } else {
            lenis.resize();
        }
    }
});

// ── Tom Select — auto-init all <select> elements ──────────
document.addEventListener('turbo:load', () => {
    if (typeof TomSelect !== 'undefined') {
        document.querySelectorAll('select').forEach(el => {
            if (el.tomselect || el.classList.contains('no-tom-select')) return;

            new TomSelect(el, {
                create: false,
                allowEmptyOption: true,
                selectOnTab: true,
                onChange(value) {
                    el.dispatchEvent(Object.assign(new Event('change', { bubbles: true }), { _fromTs: true }));
                }
            });
        });
    }
});

document.addEventListener('turbo:before-cache', () => {
    document.querySelectorAll('select').forEach(el => {
        if (el.tomselect) {
            el.tomselect.destroy();
        }
    });
});

// ── NProgress config ─────────────────────────────────────
if (typeof NProgress !== 'undefined') {
    NProgress.configure({ showSpinner: false, speed: 300, minimum: 0.08 });
}

// ── SPA AJAX Swapper Engine ──────────────────────────────
function initSpaEngine() {
    // Intercept navigation clicks
    document.addEventListener('click', e => {
        const link = e.target.closest('a');
        if (!link) return;

        // Skip target="_blank", external links, hashes, javascript:, non-spa links
        const href = link.getAttribute('href');
        if (link.target === '_blank' || 
            !href ||
            link.hostname !== window.location.hostname || 
            href.startsWith('#') || 
            href.startsWith('javascript:') ||
            link.classList.contains('no-spa')) {
            return;
        }

        e.preventDefault();
        navigateTo(link.href);
    });

    // Handle browser back/forward buttons
    window.addEventListener('popstate', () => {
        loadPage(window.location.href, false);
    });

    // Intercept form submissions
    document.addEventListener('submit', e => {
        const form = e.target;
        const action = form.getAttribute('action') || '';
        if (form.classList.contains('no-spa') || action.includes('/logout')) return;

        e.preventDefault();
        submitForm(form);
    });

    // Instant Client-Side Live Search & Filtering
    document.addEventListener('input', e => {
        const input = e.target;
        if (input.tagName === 'INPUT' && (input.type === 'text' || input.type === 'search')) {
            const form = input.closest('form.search-bar');
            if (!form) return;

            // Prevent form submission on Enter key
            form.addEventListener('submit', event => event.preventDefault());

            const query = input.value.toLowerCase().trim();

            // Determine item selector based on page container ID
            let itemSelector = '';
            if (document.getElementById('tab-murid')) {
                itemSelector = '#tab-murid .list-item';
            } else if (document.getElementById('tab-riwayat')) {
                itemSelector = '#tab-riwayat .list-item';
            } else if (document.getElementById('tab-riwayat-murid')) {
                itemSelector = '#tab-riwayat-murid .history-card';
            } else if (document.getElementById('tab-listing-report')) {
                itemSelector = '#tab-listing-report .list-item';
            }

            if (!itemSelector) return;

            const items = document.querySelectorAll(itemSelector);
            let visibleCount = 0;

            items.forEach(item => {
                const text = item.textContent.toLowerCase();
                if (text.includes(query)) {
                    item.style.display = '';
                    visibleCount++;
                } else {
                    item.style.display = 'none';
                }
            });

            // Handle empty state
            let emptyMsg = form.parentElement.querySelector('.search-empty-msg');
            if (visibleCount === 0) {
                if (!emptyMsg) {
                    emptyMsg = document.createElement('div');
                    emptyMsg.className = 'empty search-empty-msg';
                    emptyMsg.style.marginTop = '16px';
                    emptyMsg.textContent = `Tidak ada data yang cocok dengan "${input.value}".`;
                    
                    const pagination = form.parentElement.querySelector('.pagination');
                    if (pagination) pagination.style.display = 'none';

                    const listDiv = form.parentElement.querySelector('div[style*="flex-direction: column"]') || form.parentElement;
                    listDiv.appendChild(emptyMsg);
                }
            } else {
                if (emptyMsg) emptyMsg.remove();
                const pagination = form.parentElement.querySelector('.pagination');
                if (pagination) pagination.style.display = '';
            }
        }
    });
}

function navigateTo(url) {
    loadPage(url, true);
}

async function loadPage(url, pushState = true) {
    if (typeof NProgress !== 'undefined') NProgress.start();
    document.body.classList.add('turbo-loading');

    try {
        const response = await fetch(url);
        if (!response.ok) throw new Error('Network response was not ok');
        
        const html = await response.text();
        swapContent(html);

        if (pushState) {
            history.pushState(null, '', url);
        }
    } catch (error) {
        console.error('SPA load error:', error);
        window.location.href = url; // fallback to full reload
    } finally {
        if (typeof NProgress !== 'undefined') NProgress.done();
        document.body.classList.remove('turbo-loading');
    }
}

async function submitForm(form) {
    if (typeof NProgress !== 'undefined') NProgress.start();
    
    // Set submit button loading state
    const submitBtn = form.querySelector('[type="submit"]');
    if (submitBtn) {
        submitBtn.classList.add('btn-loading');
        submitBtn.disabled = true;
    }

    let action = form.getAttribute('action') || window.location.href;
    const method = (form.getAttribute('method') || 'POST').toUpperCase();
    
    // For GET forms, append serialize parameters to the action URL
    if (method === 'GET') {
        const formData = new FormData(form);
        const params = new URLSearchParams(formData);
        const url = new URL(action, window.location.origin);
        params.forEach((value, key) => {
            url.searchParams.set(key, value);
        });
        action = url.toString();
    }

    // Build request options
    const options = {
        method: method === 'GET' ? 'GET' : 'POST',
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        }
    };

    if (method !== 'GET') {
        options.body = new FormData(form);
    }

    try {
        const response = await fetch(action, options);
        if (!response.ok) throw new Error('Form submit error');

        const finalUrl = response.redirected ? response.url : response.headers.get('X-Redirect') || window.location.href;
        const html = await response.text();
        
        swapContent(html);
        
        // Update URL if redirected
        if (response.redirected) {
            history.pushState(null, '', finalUrl);
        }
    } catch (error) {
        console.error('SPA form submit error:', error);
        // Fallback: standard submit if something goes wrong
        form.submit();
    } finally {
        if (typeof NProgress !== 'undefined') NProgress.done();
        if (submitBtn) {
            submitBtn.classList.remove('btn-loading');
            submitBtn.disabled = false;
        }
    }
}

function swapContent(htmlString) {
    console.log('[SPA Search] swapContent called with html length:', htmlString.length);
    const parser = new DOMParser();
    const doc = parser.parseFromString(htmlString, 'text/html');
    
    const newApp = doc.querySelector('.app');
    const currentApp = document.querySelector('.app');
    
    console.log('[SPA Search] newApp found:', !!newApp, 'currentApp found:', !!currentApp);
    if (newApp && currentApp) {
        // Find success/error alerts in the new HTML
        const successAlert = newApp.querySelector('.alert-success');
        const dangerAlert = newApp.querySelector('.alert-danger');

        if (successAlert) {
            const msg = successAlert.textContent.trim();
            successAlert.remove(); // hide from displaying static banner
            window.showToast(msg);
        } else if (dangerAlert) {
            const msg = dangerAlert.textContent.trim();
            dangerAlert.remove();
            window.showToast(msg);
        }

        // Swap app wrapper content
        currentApp.innerHTML = newApp.innerHTML;
        
        // Close modals if any are open
        const modals = document.querySelectorAll('.modal-backdrop');
        modals.forEach(modal => modal.classList.remove('show'));
        
        // Re-dispatch turbo:load to re-initialize scripts
        document.dispatchEvent(new Event('turbo:load'));
    } else {
        // Fallback
        window.location.reload();
    }
}

// Start the SPA Engine
initSpaEngine();

// Fire initial turbo:load event on first load
document.addEventListener('DOMContentLoaded', () => {
    // Check if there are any alert notifications on first page load
    const initialAlert = document.querySelector('.alert-success');
    if (initialAlert) {
        const msg = initialAlert.textContent.trim();
        initialAlert.remove();
        window.showToast(msg);
    }
    
    document.dispatchEvent(new Event('turbo:load'));
});

// ── Toast ─────────────────────────────────────────────────
let toastTimer;
function showToast(msg) {
    const el = document.getElementById('toast');
    if (!el) return;
    el.textContent = msg;
    el.classList.add('show');
    clearTimeout(toastTimer);
    toastTimer = setTimeout(() => el.classList.remove('show'), 2200);
}
window.showToast = showToast;

// ── Universal Delete Modal ────────────────────────────────
function openDeleteModal(actionUrl, message) {
    const modal = document.getElementById('deleteModal');
    const form = document.getElementById('deleteModalForm');
    const msgEl = document.getElementById('deleteModalMessage');
    if (!modal || !form || !msgEl) return;
    
    form.action = actionUrl;
    msgEl.textContent = message || 'Apakah Anda yakin ingin menghapus data ini?';
    modal.classList.add('show');
}
window.openDeleteModal = openDeleteModal;

function closeDeleteModal() {
    const modal = document.getElementById('deleteModal');
    if (modal) {
        modal.classList.remove('show');
    }
}
window.closeDeleteModal = closeDeleteModal;

document.addEventListener('turbo:load', () => {
    closeDeleteModal();
});
