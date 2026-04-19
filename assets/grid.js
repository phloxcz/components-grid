/**
 * GridControl – JS
 *
 * All features use event delegation — no re-init needed after snippet update.
 * Search and filters are confirmed by Enter or clicking Použít.
 *
 * Setup (AJAX — loading indicator):
 *   import naja from 'naja';
 *   import GridInit from './grid.js';
 *   naja.initialize();
 *   GridInit(naja);
 *
 * Setup (no AJAX):
 *   import './grid.js';
 */

// ── Naja reference (set via hookNaja or GridInit) ─────────────────────────────
let _naja = null;

// ── Dialog adapter (Capify if available, native browser dialogs otherwise) ────
//
// Capify (phloxcz/capify) provides themed alert/confirm/prompt dialogs.
// If loaded on the page (window.Capify), grid uses it automatically.
// Otherwise falls back to native window.alert / .confirm / .prompt.
//
// All three return a Promise so the API is uniform.
//
// Options object (Capify only — ignored for native fallback):
//   { title, okText, cancelText, anchor, placement, danger }

function gridAlert(message, opts = {}) {
    if (window.Capify?.alert) {
        return Promise.resolve(window.Capify.alert(message, opts));
    }
    window.alert(message);
    return Promise.resolve();
}

function gridConfirm(message, opts = {}) {
    if (window.Capify?.confirm) {
        const capifyOpts = { ...opts };
        // 'danger: true' shortcut → red OK button
        if (opts.danger) {
            capifyOpts.classes = { btnOk: 'btn btn-danger', ...opts.classes };
        }
        return Promise.resolve(window.Capify.confirm(message, capifyOpts));
    }
    return Promise.resolve(window.confirm(message));
}

function gridPrompt(message, opts = {}) {
    if (window.Capify?.prompt) {
        const capifyOpts = { ...opts };
        // If anchor element provided, use popover variant for compact UX
        if (opts.anchor && !capifyOpts.variant) {
            capifyOpts.variant = 'popover';
            capifyOpts.placement = capifyOpts.placement || 'bottom-left';
        }
        return Promise.resolve(window.Capify.prompt(message, capifyOpts));
    }
    return Promise.resolve(window.prompt(message));
}

// ── Bulk checkboxes ───────────────────────────────────────────────────────────

function bulkUpdate(wrapper) {
    const checkAll = wrapper.querySelector('.phx-grid-check-all');
    const checks   = [...wrapper.querySelectorAll('.phx-grid-row-check')];
    const count    = checks.filter(c => c.checked).length;
    const hasAny   = count > 0;

    if (checkAll) {
        checkAll.checked       = hasAny && count === checks.length;
        checkAll.indeterminate = hasAny && count < checks.length;
    }

    // Bulk action bar — always visible, update count and button state
    const bulkBar = wrapper.querySelector('.phx-grid-bulk-bar');
    const countEl = wrapper.querySelector('.phx-grid-bulk-bar-count');
    if (countEl) {
        const tpl = bulkBar?.dataset.bulkText || '{count} selected';
        countEl.textContent = tpl.replace('{count}', count);
    }
    wrapper.querySelectorAll('.phx-grid-bulk-btn').forEach(btn => {
        btn.disabled = !hasAny;
    });
}

document.addEventListener('change', e => {
    const wrapper = e.target.closest('.phx-grid-wrapper');
    if (!wrapper) return;
    if (e.target.classList.contains('phx-grid-check-all')) {
        wrapper.querySelectorAll('.phx-grid-row-check').forEach(c => { c.checked = e.target.checked; });
    }
    if (e.target.classList.contains('phx-grid-check-all') ||
        e.target.classList.contains('phx-grid-row-check')) {
        bulkUpdate(wrapper);
    }
});


// Run bulkUpdate on page load
document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('.phx-grid-wrapper').forEach(bulkUpdate);
});

// ── Action column dropdown menu ───────────────────────────────────────────────

document.addEventListener('click', e => {
    const toggle = e.target.closest('.phx-grid-action-toggle');

    if (toggle) {
        e.stopPropagation();
        const menu    = toggle.nextElementSibling ?? document.querySelector(`[data-phx-grid-owner="${toggle.dataset.phxGridUid}"]`);
        const isOpen  = toggle.getAttribute('aria-expanded') === 'true';
        // Close all open menus first
        gridCloseAllActionMenus();
        if (!isOpen && menu) {
            gridOpenActionMenu(toggle, menu);
        }
        return;
    }

    // Close on outside click
    if (!e.target.closest('.phx-grid-action-menu')) {
        gridCloseAllActionMenus();
    }
});

// ── Action menu helpers (teleport to body to avoid overflow clipping) ─────────

let _actionMenuUid = 0;

/**
 * Apply inline styles for a teleported dropdown menu.
 * Sets position:fixed + visual appearance so the menu looks correct
 * regardless of its position in the DOM.
 */
function gridApplyMenuStyles(menu) {
    menu.style.display      = 'block';
    menu.style.position     = 'fixed';
    menu.style.zIndex       = '9999';
    menu.style.left         = '';
    menu.style.right        = 'auto';
    menu.style.top          = '';
    menu.style.bottom       = 'auto';
    menu.style.margin       = '0';
    menu.style.width        = 'max-content';
    // Visual styles — inline so they survive teleport and beat any CSS specificity
    menu.style.background   = 'var(--bs-dropdown-bg, var(--bs-body-bg, #fff))';
    menu.style.border       = '1px solid var(--bs-dropdown-border-color, rgba(0,0,0,.15))';
    menu.style.borderRadius = 'var(--bs-dropdown-border-radius, .375rem)';
    menu.style.boxShadow    = '0 .5rem 1rem rgba(0,0,0,.15)';
    menu.style.padding      = '.5rem 0';
    menu.style.listStyle    = 'none';
}

/** Reset all inline styles set by gridApplyMenuStyles. */
function gridResetMenuStyles(menu) {
    menu.style.display      = '';
    menu.style.position     = '';
    menu.style.zIndex       = '';
    menu.style.left         = '';
    menu.style.right        = '';
    menu.style.top          = '';
    menu.style.bottom       = '';
    menu.style.margin       = '';
    menu.style.width        = '';
    menu.style.background   = '';
    menu.style.border       = '';
    menu.style.borderRadius = '';
    menu.style.boxShadow    = '';
    menu.style.padding      = '';
    menu.style.listStyle    = '';
}

function gridOpenActionMenu(toggle, menu) {
    // Assign a unique ID to link toggle ↔ menu after DOM move
    const uid = '_phxam' + (++_actionMenuUid);
    toggle.dataset.phxGridUid = uid;
    menu.dataset.phxGridOwner = uid;

    // Remember original parent so we can move it back
    menu._phxOriginalParent = menu.parentElement;
    menu._phxOriginalNext   = menu.nextElementSibling;

    // Move to wrapper (outside table, but still scoped)
    const wrapper = toggle.closest('.phx-grid-wrapper');
    (wrapper || document.body).appendChild(menu);

    // Copy data-active-preset to menu so preset actions work after teleport
    const presetGroup = toggle.closest('.phx-grid-preset-actions');
    if (presetGroup) {
        menu.dataset.activePreset = presetGroup.dataset.activePreset || '';
    }

    // Position fixed relative to toggle button
    const rect = toggle.getBoundingClientRect();
    gridApplyMenuStyles(menu);

    // Measure menu
    const menuRect = menu.getBoundingClientRect();

    // Horizontal: if toggle is in the right half of viewport, align right edges;
    // otherwise align left edges.
    const viewMid = window.innerWidth / 2;
    const toggleMid = rect.left + rect.width / 2;

    if (toggleMid > viewMid) {
        // Right-aligned: menu right edge = toggle right edge
        let left = rect.right - menuRect.width;
        if (left < 0) left = 0;
        menu.style.left = left + 'px';
    } else {
        // Left-aligned: menu left edge = toggle left edge
        let left = rect.left;
        if (left + menuRect.width > window.innerWidth) left = window.innerWidth - menuRect.width;
        menu.style.left = left + 'px';
    }

    // Vertical: prefer below, flip above if needed
    if (rect.bottom + menuRect.height <= window.innerHeight) {
        menu.style.top = rect.bottom + 2 + 'px';
    } else {
        menu.style.top = (rect.top - menuRect.height - 2) + 'px';
    }

    toggle.setAttribute('aria-expanded', 'true');
}

function gridCloseAllActionMenus() {
    document.querySelectorAll('.phx-grid-action-toggle[aria-expanded="true"]').forEach(t => {
        t.setAttribute('aria-expanded', 'false');
        const uid  = t.dataset.phxGridUid;
        const menu = uid ? document.querySelector(`[data-phx-grid-owner="${uid}"]`) : t.nextElementSibling;
        if (menu) {
            menu.style.display = 'none';
            gridResetMenuStyles(menu);
            // Move back to original parent
            if (menu._phxOriginalParent) {
                if (menu._phxOriginalNext) {
                    menu._phxOriginalParent.insertBefore(menu, menu._phxOriginalNext);
                } else {
                    menu._phxOriginalParent.appendChild(menu);
                }
                menu._phxOriginalParent = null;
                menu._phxOriginalNext   = null;
            }
            delete t.dataset.phxGridUid;
            delete menu.dataset.phxGridOwner;
        }
    });
}

// ── Escape key — close all open panels ────────────────────────────────────────

document.addEventListener('keydown', e => {
    if (e.key !== 'Escape') return;
    let closed = false;
    // Action column menus
    gridCloseAllActionMenus();
    // (can't easily detect if any were open, just always proceed)
    // MultiSelect / range filter panels
    document.querySelectorAll('[data-ms-panel]').forEach(p => {
        if (msClose(p)) closed = true;
    });
    // Column toggle panel
    document.querySelectorAll('[data-coltoggle-panel]').forEach(p => {
        if (p.style.display !== 'none') {
            p.style.display = 'none';
            closed = true;
        }
    });
    document.querySelectorAll('[data-coltoggle-trigger]').forEach(t => {
        t.setAttribute('aria-expanded', 'false');
    });
});

// Close teleported menus on scroll (fixed position doesn't follow scroll)
document.addEventListener('scroll', () => gridCloseAllActionMenus(), { passive: true, capture: true });

// ── Per-page selector ─────────────────────────────────────────────────────────

document.addEventListener('change', e => {
    const sel = e.target;
    if (!sel.classList.contains('phx-grid-per-page-select')) return;
    const url = sel.dataset.perpageUrl?.replace('__pp__', encodeURIComponent(sel.value));
    if (!url) return;
    if (_naja) {
        _naja.makeRequest('GET', url);
    } else {
        window.location.href = url;
    }
});

// ── MultiSelect filter dropdown ───────────────────────────────────────────────

// Track which panels have uncommitted changes since they were opened.
const msDirty = new WeakSet();

function msSnapshot(panel) {
    // Store serialized state so we can detect changes on close.
    const inputs = [...panel.querySelectorAll('.phx-grid-ms-check, .phx-grid-range-input')];
    panel.dataset.msSnapshot = inputs.map(i =>
        i.type === 'checkbox' ? (i.checked ? '1' : '0') : i.value
    ).join(',');
}

function msIsDirty(panel) {
    const inputs = [...panel.querySelectorAll('.phx-grid-ms-check, .phx-grid-range-input')];
    const current = inputs.map(i =>
        i.type === 'checkbox' ? (i.checked ? '1' : '0') : i.value
    ).join(',');
    return current !== (panel.dataset.msSnapshot ?? '');
}

function msOpen(panel, toggle) {
    msSnapshot(panel);
    panel.style.display = 'block';
    toggle?.setAttribute('aria-expanded', 'true');
}

function msClose(panel) {
    if (!panel || panel.style.display === 'none') return false;
    panel.style.display = 'none';
    const wrap = panel.closest('.phx-grid-ms-wrap');
    wrap?.querySelector('[data-ms-toggle]')?.setAttribute('aria-expanded', 'false');
    return true; // was open
}

function msSubmitIfDirty(panel) {
    if (!msIsDirty(panel)) return;
    const wrap   = panel.closest('.phx-grid-ms-wrap') ?? panel.closest('.phx-grid-filter-cell-wrap');
    const input  = panel.querySelector('.phx-grid-ms-check, .phx-grid-range-input');
    const formId = input?.getAttribute('form');
    const form   = formId ? document.getElementById(formId) : wrap?.closest('form');
    if (form) form.requestSubmit();
}

function msSubmit(panel) {
    // Unconditional submit — used by the Použít button
    const wrap   = panel.closest('.phx-grid-ms-wrap') ?? panel.closest('.phx-grid-filter-cell-wrap');
    const input  = panel.querySelector('.phx-grid-ms-check, .phx-grid-range-input');
    const formId = input?.getAttribute('form');
    const form   = formId ? document.getElementById(formId) : wrap?.closest('form');
    if (form) form.requestSubmit();
}

document.addEventListener('click', e => {
    const toggle = e.target.closest('[data-ms-toggle]');

    if (toggle) {
        const panel = toggle.closest('.phx-grid-ms-wrap')?.querySelector('[data-ms-panel]');
        if (!panel) return;
        const isOpen = panel.style.display !== 'none';

        // Close all other open panels, submit if dirty
        document.querySelectorAll('[data-ms-panel]').forEach(p => {
            if (p !== panel && msClose(p)) msSubmitIfDirty(p);
        });

        if (!isOpen) {
            msOpen(panel, toggle);
        }
        return;
    }

    // Click outside — close and submit only if dirty
    if (!e.target.closest('.phx-grid-ms-wrap')) {
        document.querySelectorAll('[data-ms-panel]').forEach(p => {
            if (msClose(p)) msSubmitIfDirty(p);
        });
    }

    // OK button — always submit
    const applyBtn = e.target.closest('[data-ms-apply]');
    if (applyBtn) {
        const panel = applyBtn.closest('[data-ms-panel]');
        if (panel && msClose(panel)) msSubmit(panel);
    }
});

// Update clear button state on checkbox change (no submit here)
document.addEventListener('change', e => {
    const check = e.target;
    if (!check.classList.contains('phx-grid-ms-check')) return;
    if (!check.closest('.phx-grid-wrapper')) return;

    const wrap   = check.closest('.phx-grid-ms-wrap');
    const fkey   = check.name.match(/mfilters\[(.+?)\]/)?.[1];
    if (fkey) {
        const clearBtn = wrap?.closest('.phx-grid-filter-cell-wrap')?.querySelector('[data-clear-ms]');
        if (clearBtn) {
            clearBtn.disabled = !wrap?.querySelector('.phx-grid-ms-check:checked');
        }
    }
});

// Clear multiselect filter
document.addEventListener('click', e => {
    const btn = e.target.closest('[data-clear-ms]');
    if (!btn || !btn.closest('.phx-grid-wrapper')) return;
    e.preventDefault();
    const fkey   = btn.dataset.clearMs;
    const formId = btn.getAttribute('form');
    const form   = formId ? document.getElementById(formId) : btn.closest('form');
    if (!form) return;
    document.querySelectorAll(`[form="${formId}"][name="mfilters[${fkey}][]"]`).forEach(cb => { cb.checked = false; });
    btn.disabled = true;
    form.requestSubmit();
});

document.addEventListener('click', e => {
    const btn = e.target.closest('.phx-grid-filter-clear');
    if (!btn || !btn.closest('.phx-grid-wrapper')) return;
    e.preventDefault();

    const formId = btn.getAttribute('form');
    const form   = formId ? document.getElementById(formId) : btn.closest('form');
    if (!form) return;

    // Clear primary field and optional secondary field (range filters)
    const keys = [btn.dataset.clearFilter, btn.dataset.clearFilterAlso].filter(Boolean);
    keys.forEach(key => {
        const input = document.querySelector(`[form="${formId}"][name="filters[${key}]"]`)
            ?? form.querySelector(`[name="filters[${key}]"]`);
        if (!input) return;
        input.value = '';
        // For DDL: also reset the visible trigger label
        const wrap = input.closest('.dropdownlist-wrapper');
        if (wrap) {
            const label = wrap.querySelector('.dropdownlist-trigger-text');
            if (label) {
                label.textContent = '';
                label.classList.add('dropdownlist-trigger-placeholder');
            }
        }
    });

    form.requestSubmit();
});

// ── Always-on filter auto-submit (works with and without AJAX) ────────────────
// Bool selects submit immediately on change.
// Date range inputs submit on calendar pick (change without keyboard activity).
// Enter in any writable filter field submits immediately.

// Track whether the user is actively typing into a date field.
// Set on keydown, cleared on blur. If 'change' fires while this is true,
// it came from keyboard segment navigation → ignore.
const dateTyping = new WeakSet();

document.addEventListener('keydown', e => {
    const el = e.target;
    if (el.tagName.toLowerCase() === 'input' && el.type === 'date') {
        dateTyping.add(el);
    }
}, true);

document.addEventListener('blur', e => {
    const el = e.target;
    if (el.tagName.toLowerCase() === 'input' && el.type === 'date') {
        dateTyping.delete(el);
    }
}, true);

document.addEventListener('change', e => {
    const el = e.target;
    if (!el.closest('.phx-grid-wrapper')) return;

    const isSelect = el.tagName.toLowerCase() === 'select' && el.classList.contains('phx-grid-filter-input');
    const isDate   = el.tagName.toLowerCase() === 'input'  && el.type === 'date' && el.classList.contains('phx-grid-range-input');
    // Don't auto-submit date inputs inside a dropdown panel — user confirms with Použít
    if (isDate && el.closest('[data-ms-panel]')) return;
    // DropDownList trigger dispatches 'change' on the <button> after a pick
    const isDdl    = el.tagName.toLowerCase() === 'button' && el.classList.contains('phx-grid-ddl-trigger');
    if (!isSelect && !isDate && !isDdl) return;

    // Ignore change events that originated from keyboard (segment navigation).
    // Calendar picks arrive without prior keydown → dateTyping flag not set.
    // DropDownList change always comes from a deliberate pick → never skip.
    if (isDate && dateTyping.has(el)) return;

    // For DropDownList, the hidden input (sibling) carries the form= attribute
    const formEl = isDdl
        ? el.parentElement?.querySelector('input[type="hidden"]')
        : el;
    const formId = formEl?.getAttribute('form');
    const form   = formId ? document.getElementById(formId) : el.closest('form');
    if (form) form.requestSubmit();
});

document.addEventListener('keydown', e => {
    if (e.key !== 'Enter') return;
    const el = e.target;
    if (!el.closest('.phx-grid-wrapper')) return;
    const isFilter = el.classList.contains('phx-grid-filter-input') && !el.classList.contains('phx-grid-suggest-input');
    const isRange  = el.classList.contains('phx-grid-range-input');
    if (!isFilter && !isRange) return;
    // Range inputs inside a dropdown panel submit via the Použít button, not Enter
    if (isRange && el.closest('[data-ms-panel]')) return;

    const formId = el.getAttribute('form');
    const form   = formId ? document.getElementById(formId) : el.closest('form');
    if (form) { e.preventDefault(); form.requestSubmit(); }
});

// ── AJAX features: loading indicator + snippet re-init ──────────────────────

// Confirm dialogs — event delegation
//
// Handles links / buttons with data-confirm="message" attribute.
// Native confirm() is synchronous, so the simple case just blocks the event.
// Capify is async, so we always preventDefault, then re-dispatch the action
// (link navigation or button click) only after the user confirms.
document.addEventListener('click', e => {
    const el = e.target.closest('[data-confirm]');
    if (!el) return;
    // Skip if already confirmed (re-dispatched click, see below)
    if (el.dataset.confirmed === '1') {
        delete el.dataset.confirmed;
        return;
    }

    // Native sync path — keep simple behavior when Capify is not available
    if (!window.Capify?.confirm) {
        if (!window.confirm(el.dataset.confirm)) {
            e.preventDefault();
            e.stopPropagation();
        }
        return;
    }

    // Async path (Capify) — always preventDefault, ask, then re-trigger
    e.preventDefault();
    e.stopPropagation();
    Promise.resolve(window.Capify.confirm(el.dataset.confirm)).then(ok => {
        if (!ok) return;
        el.dataset.confirmed = '1';
        // Re-dispatch the click — confirm guard above will pass through
        el.click();
    });
});

function hookNaja(naja) {
    _naja = naja;
    naja.addEventListener('start', () =>
        document.querySelectorAll('.phx-grid-table').forEach(t => t.classList.add('phx-grid-loading'))
    );
    naja.addEventListener('complete', () =>
        document.querySelectorAll('.phx-grid-table').forEach(t => t.classList.remove('phx-grid-loading'))
    );
    naja.snippetHandler.addEventListener('afterUpdate', ({ detail }) => {
        const snippet = detail.snippet;
        const wrapper = snippet instanceof Element
            ? (snippet.classList.contains('phx-grid-wrapper') ? snippet : snippet.querySelector('.phx-grid-wrapper'))
            : null;
        if (wrapper) {
            bulkUpdate(wrapper);
            gridApplySavedLayout(wrapper);
        }
        // If snippet is inside a wrapper (e.g. snippet=grid), find parent wrapper
        if (!wrapper && snippet instanceof Element) {
            const parentWrapper = snippet.closest('.phx-grid-wrapper');
            if (parentWrapper) gridApplySavedLayout(parentWrapper);
        }
    });
}

document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('.phx-grid-wrapper').forEach(w => {
        bulkUpdate(w);
        gridApplySavedLayout(w);
    });

    if (window.naja) {
        hookNaja(window.naja);
    } else {
        window.addEventListener('naja:init', () => hookNaja(window.naja), { once: true });
    }
});

// ── Column resize & reorder ─────────────────────────────────────────────────

// --- Storage helpers ---

function gridStorageKey(wrapper, suffix) {
    const id = wrapper.dataset.gridId || 'default';
    return `phx-grid-${id}-${suffix}`;
}

function gridSaveWidths(wrapper) {
    if (wrapper.hasAttribute('data-presets')) return;
    const table = wrapper.querySelector('table');
    if (!table) return;
    const ths = table.querySelectorAll('thead > tr:first-child > th[data-col-key]');
    const widths = {};
    ths.forEach(th => { widths[th.dataset.colKey] = th.style.width || ''; });
    try { localStorage.setItem(gridStorageKey(wrapper, 'colWidths'), JSON.stringify(widths)); } catch {}
}

function gridSaveOrder(wrapper) {
    if (wrapper.hasAttribute('data-presets')) return;
    const table = wrapper.querySelector('table');
    if (!table) return;
    const ths = table.querySelectorAll('thead > tr:first-child > th[data-col-key]');
    const order = [...ths].map(th => th.dataset.colKey);
    try { localStorage.setItem(gridStorageKey(wrapper, 'colOrder'), JSON.stringify(order)); } catch {}
}

function gridApplySavedLayout(wrapper) {
    const table = wrapper.querySelector('table');
    if (!table) return;

    // When presets are managed server-side, the server already rendered
    // the correct column order, widths, and visibility — skip localStorage.
    if (wrapper.hasAttribute('data-presets')) return;

    // Apply saved column order
    if (wrapper.hasAttribute('data-reorderable')) {
        try {
            const orderJson = localStorage.getItem(gridStorageKey(wrapper, 'colOrder'));
            if (orderJson) {
                const order = JSON.parse(orderJson);
                gridReorderColumns(table, order);
            }
        } catch {}
    }

    // Apply saved column widths
    if (wrapper.hasAttribute('data-resizable')) {
        try {
            const widthsJson = localStorage.getItem(gridStorageKey(wrapper, 'colWidths'));
            if (widthsJson) {
                const widths = JSON.parse(widthsJson);
                const hasAny = Object.values(widths).some(w => w !== '');
                if (hasAny) {
                    table.style.tableLayout = 'fixed';
                    table.style.width = '100%';
                }
                const ths = table.querySelectorAll('thead > tr:first-child > th[data-col-key]');
                ths.forEach(th => {
                    const w = widths[th.dataset.colKey];
                    if (w) th.style.width = w;
                });
                const cols = table.querySelectorAll('colgroup > col[data-col-key]');
                cols.forEach(col => {
                    const w = widths[col.dataset.colKey];
                    if (w) col.style.width = w;
                });
            }
        } catch {}
    }

    // Apply saved column visibility
    gridApplyVisibility(wrapper);
}

// --- Column resize ---

let resizeState = null;

document.addEventListener('mousedown', e => {
    const handle = e.target.closest('.phx-grid-resize-handle');
    if (!handle) return;
    const th = handle.closest('th[data-col-key]');
    const table = th?.closest('table');
    const wrapper = th?.closest('.phx-grid-wrapper');
    if (!th || !table || !wrapper || !wrapper.hasAttribute('data-resizable')) return;
    e.preventDefault();
    e.stopPropagation();

    // On first resize, snapshot all column widths and switch to fixed layout
    if (table.style.tableLayout !== 'fixed') {
        const allThs = table.querySelectorAll('thead > tr:first-child > th');
        allThs.forEach(t => { t.style.width = t.offsetWidth + 'px'; });
        table.style.tableLayout = 'fixed';
        table.style.width = '100%';
    }

    const startX = e.clientX;
    const startWidth = th.offsetWidth;
    handle.classList.add('phx-grid-resizing');
    wrapper.classList.add('phx-grid-col-resizing');

    resizeState = { th, table, wrapper, handle, startX, startWidth };
});

document.addEventListener('mousemove', e => {
    if (!resizeState) return;
    const { th, startX, startWidth } = resizeState;
    const newWidth = Math.max(40, startWidth + (e.clientX - startX));
    th.style.width = newWidth + 'px';
    // Sync <col>
    const col = th.closest('table')?.querySelector(`col[data-col-key="${th.dataset.colKey}"]`);
    if (col) col.style.width = newWidth + 'px';
});

document.addEventListener('mouseup', e => {
    if (!resizeState) return;
    resizeState.handle.classList.remove('phx-grid-resizing');
    resizeState.wrapper.classList.remove('phx-grid-col-resizing');
    gridSaveWidths(resizeState.wrapper);
    resizeState = null;
});

// --- Column drag-and-drop reorder ---

let dragColKey = null;

// Prevent drag when starting from resize handle
document.addEventListener('mousedown', e => {
    if (e.target.closest('.phx-grid-resize-handle')) {
        const th = e.target.closest('th[data-col-key]');
        if (th) th.setAttribute('draggable', 'false');
    }
}, true);

document.addEventListener('mouseup', () => {
    // Restore draggable after resize handle interaction
    document.querySelectorAll('th[data-col-key][draggable="false"]').forEach(th => {
        th.setAttribute('draggable', 'true');
    });
}, true);

document.addEventListener('dragstart', e => {
    const th = e.target.closest('th[data-col-key]');
    if (!th || !th.closest('.phx-grid-wrapper')?.hasAttribute('data-reorderable')) return;
    // Don't drag if initiated from resize handle (safety net)
    if (e.target.closest('.phx-grid-resize-handle')) { e.preventDefault(); return; }

    dragColKey = th.dataset.colKey;
    th.classList.add('phx-grid-drag-source');
    e.dataTransfer.effectAllowed = 'move';
    e.dataTransfer.setData('text/plain', dragColKey);

    const wrapper = th.closest('.phx-grid-wrapper');
    if (wrapper) wrapper.classList.add('phx-grid-col-dragging');
});

document.addEventListener('dragover', e => {
    const th = e.target.closest('th[data-col-key]');
    if (!th || !dragColKey || th.dataset.colKey === dragColKey) return;
    e.preventDefault();
    e.dataTransfer.dropEffect = 'move';

    // Show left/right indicator based on mouse position within the TH
    const rect = th.getBoundingClientRect();
    const mid = rect.left + rect.width / 2;
    th.classList.toggle('phx-grid-drag-over-left', e.clientX < mid);
    th.classList.toggle('phx-grid-drag-over-right', e.clientX >= mid);
});

document.addEventListener('dragleave', e => {
    const th = e.target.closest('th[data-col-key]');
    if (th) {
        th.classList.remove('phx-grid-drag-over-left', 'phx-grid-drag-over-right');
    }
});

document.addEventListener('drop', e => {
    e.preventDefault();
    const targetTh = e.target.closest('th[data-col-key]');
    if (!targetTh || !dragColKey || targetTh.dataset.colKey === dragColKey) return;

    const table = targetTh.closest('table');
    const wrapper = targetTh.closest('.phx-grid-wrapper');
    if (!table || !wrapper) return;

    // Determine current order
    const headerThs = [...table.querySelectorAll('thead > tr:first-child > th[data-col-key]')];
    const order = headerThs.map(th => th.dataset.colKey);

    const fromIdx = order.indexOf(dragColKey);
    const toIdx = order.indexOf(targetTh.dataset.colKey);
    if (fromIdx === -1 || toIdx === -1) return;

    // Determine if drop is before or after target
    const rect = targetTh.getBoundingClientRect();
    const insertBefore = e.clientX < rect.left + rect.width / 2;

    // Build new order
    order.splice(fromIdx, 1);
    let insertIdx = order.indexOf(targetTh.dataset.colKey);
    if (!insertBefore) insertIdx++;
    order.splice(insertIdx, 0, dragColKey);

    gridReorderColumns(table, order);
    gridSaveOrder(wrapper);

    // Clean up
    targetTh.classList.remove('phx-grid-drag-over-left', 'phx-grid-drag-over-right');
});

document.addEventListener('dragend', e => {
    const th = e.target.closest('th[data-col-key]');
    if (th) th.classList.remove('phx-grid-drag-source');
    dragColKey = null;
    document.querySelectorAll('.phx-grid-drag-over-left, .phx-grid-drag-over-right')
        .forEach(el => el.classList.remove('phx-grid-drag-over-left', 'phx-grid-drag-over-right'));
    document.querySelectorAll('.phx-grid-col-dragging')
        .forEach(el => el.classList.remove('phx-grid-col-dragging'));
});

/**
 * Reorder all table cells (colgroup cols, thead ths, tbody tds, tfoot tds)
 * to match the given key order.
 */
function gridReorderColumns(table, order) {
    // Reorder <col> elements in <colgroup>
    const colgroup = table.querySelector('colgroup');
    if (colgroup) {
        const cols = [...colgroup.querySelectorAll('col[data-col-key]')];
        const colMap = Object.fromEntries(cols.map(c => [c.dataset.colKey, c]));
        // Find the first col[data-col-key] to use as reference
        const firstKeyCol = cols[0];
        const refNode = firstKeyCol; // insert before this, then it shifts
        order.forEach(key => {
            const col = colMap[key];
            if (col) colgroup.insertBefore(col, null); // append in order
        });
    }

    // Reorder cells in all rows (thead, tbody, tfoot)
    const rows = table.querySelectorAll('tr');
    rows.forEach(row => {
        const cells = [...row.querySelectorAll(':scope > th[data-col-key], :scope > td[data-col-key]')];
        if (cells.length === 0) return;
        const cellMap = Object.fromEntries(cells.map(c => [c.dataset.colKey, c]));
        order.forEach(key => {
            const cell = cellMap[key];
            if (cell) row.appendChild(cell); // moves to end in order
        });
    });
}

// ── Column visibility toggle ────────────────────────────────────────────────

// Toggle panel open/close
document.addEventListener('click', e => {
    const trigger = e.target.closest('[data-coltoggle-trigger]');
    if (trigger) {
        e.stopPropagation();
        const wrap = trigger.closest('.phx-grid-coltoggle-wrap');
        const panel = wrap?.querySelector('[data-coltoggle-panel]');
        if (!panel) return;
        const isOpen = panel.style.display !== 'none';

        // Close all other open panels
        document.querySelectorAll('[data-coltoggle-panel]').forEach(p => {
            if (p !== panel) p.style.display = 'none';
        });

        if (isOpen) {
            panel.style.display = 'none';
            trigger.setAttribute('aria-expanded', 'false');
        } else {
            // Sync checkboxes with current visibility before opening
            const wrapper = trigger.closest('.phx-grid-wrapper');
            if (wrapper) gridSyncColToggleChecks(wrapper, panel);
            panel.style.display = 'block';
            trigger.setAttribute('aria-expanded', 'true');
        }
        return;
    }

    // Close on outside click
    if (!e.target.closest('.phx-grid-coltoggle-wrap')) {
        document.querySelectorAll('[data-coltoggle-panel]').forEach(p => {
            p.style.display = 'none';
        });
        document.querySelectorAll('[data-coltoggle-trigger]').forEach(t => {
            t.setAttribute('aria-expanded', 'false');
        });
    }
});

// Checkbox change → toggle column visibility
document.addEventListener('change', e => {
    const check = e.target;
    if (!check.classList.contains('phx-grid-coltoggle-check')) return;
    const wrapper = check.closest('.phx-grid-wrapper');
    if (!wrapper) return;

    const colKey = check.value;
    const visible = check.checked;

    gridSetColumnVisible(wrapper, colKey, visible);
    gridSaveVisibility(wrapper);
});

function gridSetColumnVisible(wrapper, colKey, visible) {
    const els = wrapper.querySelectorAll(`[data-col-key="${colKey}"]`);
    els.forEach(el => {
        el.classList.toggle('phx-grid-col-hidden', !visible);
    });
}

function gridSaveVisibility(wrapper) {
    if (wrapper.hasAttribute('data-presets')) return;
    const table = wrapper.querySelector('table');
    if (!table) return;
    const ths = table.querySelectorAll('thead > tr:first-child > th[data-col-key]');
    const hidden = [];
    ths.forEach(th => {
        if (th.classList.contains('phx-grid-col-hidden')) hidden.push(th.dataset.colKey);
    });
    try { localStorage.setItem(gridStorageKey(wrapper, 'colHidden'), JSON.stringify(hidden)); } catch {}
}

function gridApplyVisibility(wrapper) {
    if (!wrapper.hasAttribute('data-coltoggle')) return;
    try {
        const json = localStorage.getItem(gridStorageKey(wrapper, 'colHidden'));
        if (!json) return;
        const hidden = JSON.parse(json);
        if (!Array.isArray(hidden)) return;
        hidden.forEach(key => gridSetColumnVisible(wrapper, key, false));
    } catch {}
}

function gridSyncColToggleChecks(wrapper, panel) {
    const table = wrapper.querySelector('table');
    if (!table) return;
    panel.querySelectorAll('.phx-grid-coltoggle-check').forEach(check => {
        const th = table.querySelector(`thead > tr:first-child > th[data-col-key="${check.value}"]`);
        check.checked = th ? !th.classList.contains('phx-grid-col-hidden') : true;
    });
}

// ── Presets ──────────────────────────────────────────────────────────────────

/**
 * Collect current grid layout state from the DOM.
 * Returns { columnOrder, columnWidths, hiddenColumns }.
 */
function gridCollectState(wrapper) {
    const table = wrapper.querySelector('table');
    if (!table) return { columnOrder: [], columnWidths: {}, hiddenColumns: [] };

    const ths = [...table.querySelectorAll('thead > tr:first-child > th[data-col-key]')];
    const columnOrder = ths.map(th => th.dataset.colKey);
    const columnWidths = {};
    const hiddenColumns = [];

    ths.forEach(th => {
        const key = th.dataset.colKey;
        if (th.style.width) columnWidths[key] = th.style.width;
        if (th.classList.contains('phx-grid-col-hidden')) hiddenColumns.push(key);
    });

    return { columnOrder, columnWidths, hiddenColumns };
}

/**
 * POST preset data via Naja (if available) or plain fetch.
 */
function gridPresetPost(url, body, wrapper) {
    const formData = new FormData();
    for (const [k, v] of Object.entries(body)) {
        formData.append(k, v);
    }

    if (_naja) {
        _naja.makeRequest('POST', url, formData);
    } else {
        // Non-AJAX fallback: hidden form submit
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = url;
        form.style.display = 'none';
        for (const [k, v] of Object.entries(body)) {
            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = k;
            input.value = v;
            form.appendChild(input);
        }
        document.body.appendChild(form);
        form.submit();
    }
}

// Preset select change → switch preset via AJAX snippet redraw
document.addEventListener('change', e => {
    const sel = e.target;
    if (!sel.classList.contains('phx-grid-preset-select')) return;

    const baseUrl = sel.dataset.presetBaseUrl;
    if (!baseUrl) return;

    const url = baseUrl.replace('__PRESET__', encodeURIComponent(sel.value));

    if (_naja) {
        // Naja GET → signal handler → finish() redraws snippets + postGet URL update
        _naja.makeRequest('GET', url);
    } else {
        window.location.href = url;
    }
});

// Preset action buttons (save, saveAs, rename, setDefault, delete)
document.addEventListener('click', async e => {
    const btn = e.target.closest('[data-preset-action]');
    if (!btn) return;

    const wrapper = btn.closest('.phx-grid-wrapper');
    if (!wrapper) return;

    const action = btn.dataset.presetAction;
    const url    = btn.dataset.url;
    if (!url) return;

    // Read active preset BEFORE closing — menu may be teleported away from its group
    const group = btn.closest('.phx-grid-preset-actions')
               || btn.closest('[data-active-preset]');
    const activeId = group?.dataset.activePreset || '';
    const hasPreset = activeId !== '' && activeId !== '_none';

    // Find a persistent anchor element (the preset Save button stays in the toolbar
    // even after dropdown menu items are teleported and closed).
    const anchor = wrapper.querySelector('.phx-grid-preset-save') || btn;

    // Close the dropdown menu (returns teleported menu to original parent)
    gridCloseAllActionMenus();

    const config = JSON.stringify(gridCollectState(wrapper));

    switch (action) {
        case 'save': {
            if (!hasPreset) {
                // No preset selected → fall back to "Save As"
                const name = await gridPrompt(btn.dataset.saveAsPrompt || 'Name:', { anchor: anchor });
                if (!name) return;
                gridPresetPost(btn.dataset.saveAsUrl, { _presetName: name, _presetConfig: config }, wrapper);
            } else {
                gridPresetPost(url, { _presetId: activeId, _presetConfig: config }, wrapper);
            }
            break;
        }

        case 'saveAs': {
            const name = await gridPrompt(btn.dataset.prompt || 'Name:', { anchor: anchor });
            if (!name) return;
            gridPresetPost(url, { _presetName: name, _presetConfig: config }, wrapper);
            break;
        }

        case 'rename': {
            if (!hasPreset) return;
            const newName = await gridPrompt(btn.dataset.prompt || 'New name:', { anchor: anchor });
            if (!newName) return;
            gridPresetPost(url, { _presetId: activeId, _presetName: newName }, wrapper);
            break;
        }

        case 'setDefault':
            if (!hasPreset) return;
            gridPresetPost(url, { _presetId: activeId }, wrapper);
            break;

        case 'delete': {
            if (!hasPreset) return;
            const confirmMsg = btn.dataset.prompt;
            if (confirmMsg) {
                const ok = await gridConfirm(confirmMsg, { danger: true, anchor: anchor });
                if (!ok) return;
            }
            gridPresetPost(url, { _presetId: activeId }, wrapper);
            break;
        }
    }
});

// Backwards-compatible export.
export default function GridInit(naja) {
    hookNaja(naja);
}
