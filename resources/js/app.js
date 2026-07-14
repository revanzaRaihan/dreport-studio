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
        if (form.classList.contains('no-spa') || form.getAttribute('action') === '/logout') return;

        e.preventDefault();
        submitForm(form);
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

    const action = form.getAttribute('action') || window.location.href;
    const method = (form.getAttribute('method') || 'POST').toUpperCase();
    
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
    const parser = new DOMParser();
    const doc = parser.parseFromString(htmlString, 'text/html');
    
    const newApp = doc.querySelector('.app');
    const currentApp = document.querySelector('.app');
    
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
