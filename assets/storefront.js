/* Storefront progressive enhancement.
 * Menu, accordions, quantity steppers, checkout shipping-address toggle.
 * Colour and size selection work without JS (links / native radios). */

function ready(fn) {
    if (document.readyState !== 'loading') fn();
    else document.addEventListener('DOMContentLoaded', fn);
}

ready(() => {
    // --- category dropdown menu: sync aria-expanded on hover/focus ---
    document.querySelectorAll('.subnav .navp').forEach((li) => {
        const link = li.querySelector('a');
        const set = (v) => link.setAttribute('aria-expanded', v ? 'true' : 'false');
        li.addEventListener('mouseenter', () => set(true));
        li.addEventListener('mouseleave', () => set(false));
        li.addEventListener('focusin', () => set(true));
        li.addEventListener('focusout', (e) => { if (!li.contains(e.relatedTarget)) set(false); });
    });
    document.addEventListener('keydown', (e) => {
        if (e.key !== 'Escape') return;
        const open = document.querySelector('.subnav .navp > a[aria-expanded="true"]');
        if (open) { open.setAttribute('aria-expanded', 'false'); open.blur(); }
    });

    // --- accordion +/- marker ---
    document.querySelectorAll('.acc details').forEach((d) => {
        const mark = d.querySelector('summary > span');
        if (mark) d.addEventListener('toggle', () => { mark.textContent = d.open ? '−' : '+'; });
    });

    // --- quantity steppers ---
    document.querySelectorAll('.qty').forEach((q) => {
        const input = q.querySelector('input');
        if (!input) return;
        const step = (delta) => {
            const min = parseInt(input.min || '1', 10);
            const max = parseInt(input.max || '99', 10);
            const next = Math.min(max, Math.max(min, (parseInt(input.value, 10) || min) + delta));
            input.value = String(next);
            input.dispatchEvent(new Event('change', { bubbles: true }));
        };
        q.querySelector('.qminus')?.addEventListener('click', () => step(-1));
        q.querySelector('.qplus')?.addEventListener('click', () => step(1));
    });

    // --- checkout: reveal shipping-address fields ---
    document.querySelectorAll('input[name="shipToDifferent"]').forEach((cb) => {
        const box = document.querySelector('[data-ship-fields]');
        const sync = () => { if (box) box.hidden = !cb.checked; };
        cb.addEventListener('change', sync);
        sync();
    });

    // --- skip link moves focus to main content ---
    const skip = document.querySelector('.skip');
    if (skip) skip.addEventListener('click', () => {
        setTimeout(() => document.getElementById('obsah')?.focus(), 0);
    });
});
