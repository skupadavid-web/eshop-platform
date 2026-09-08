/* Storefront progressive enhancement — E1.
 * Menus, accordions and the demo swatch/size/option toggles.
 * Real add-to-cart / filtering behaviour arrives in E4–E5. */

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

    // --- demo toggles (visual only until E4/E5) ---
    const groupToggle = (btn, groupSel) => {
        btn.closest(groupSel)?.querySelectorAll('button').forEach((b) => b.setAttribute('aria-pressed', 'false'));
        btn.setAttribute('aria-pressed', 'true');
    };
    document.querySelectorAll('.sizerow .sizechip:not([disabled])').forEach((b) =>
        b.addEventListener('click', () => groupToggle(b, '.sizerow')));
    document.querySelectorAll('.pd-buy .swatchrow .swatch, .pd-thumbs .pd-thumb').forEach((b) =>
        b.addEventListener('click', () => {
            document.querySelectorAll('.pd-buy .swatchrow .swatch, .pd-thumbs .pd-thumb')
                .forEach((x) => x.setAttribute('aria-pressed', 'false'));
            b.setAttribute('aria-pressed', 'true');
        }));
    document.querySelectorAll('.opt-cards').forEach((group) =>
        group.querySelectorAll('.opt').forEach((b) =>
            b.addEventListener('click', () => groupToggle(b, '.opt-cards'))));

    // --- skip link moves focus to main content ---
    const skip = document.querySelector('.skip');
    if (skip) skip.addEventListener('click', () => {
        setTimeout(() => document.getElementById('obsah')?.focus(), 0);
    });
});
