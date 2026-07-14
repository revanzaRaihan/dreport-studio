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

// ── NProgress config & Turbo integrations ────────────────
if (typeof NProgress !== 'undefined') {
    NProgress.configure({ showSpinner: false, speed: 300, minimum: 0.08 });

    let visitInProgress = false;

    document.addEventListener('turbo:before-visit', e => {
        if (visitInProgress) {
            e.preventDefault();
            e.stopImmediatePropagation();
            return;
        }
        visitInProgress = true;
        document.body.classList.add('turbo-loading');
    });

    document.addEventListener('turbo:visit', () => NProgress.start());
    document.addEventListener('turbo:submit-start', () => NProgress.start());
    
    document.addEventListener('turbo:load', () => {
        NProgress.done();
        visitInProgress = false;
        document.body.classList.remove('turbo-loading');
    });

    document.addEventListener('turbo:request-end', () => {
        visitInProgress = false;
        document.body.classList.remove('turbo-loading');
    });
}

// Prevent double submission of forms
document.addEventListener('submit', e => {
    const form = e.target;
    if (form.dataset.submitting) {
        e.preventDefault();
        e.stopImmediatePropagation();
        return;
    }
    form.dataset.submitting = 'true';
});

// Add loading state to form submit buttons
document.addEventListener('turbo:submit-start', e => {
    const btn = e.target.querySelector('[type="submit"]');
    if (btn) {
        btn.classList.add('btn-loading');
        btn.disabled = true;
    }
});

document.addEventListener('turbo:submit-end', e => {
    const form = e.target;
    delete form.dataset.submitting;
    const btn = form.querySelector('[type="submit"]');
    if (btn) {
        btn.classList.remove('btn-loading');
        btn.disabled = false;
    }
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
