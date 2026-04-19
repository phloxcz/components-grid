<?php declare(strict_types=1);

namespace Phlox\Components\Grid\Theme;

/**
 * Theme – CSS class definitions for all GridControl elements.
 *
 * Usage:
 *   // Bootstrap 5 (default)
 *   $grid->setTheme(GridControl::BOOTSTRAP);
 *   $grid->setTheme(Theme::bootstrap());
 *
 *   // Tailwind CSS example
 *   $grid->setTheme(Theme::tailwind());
 *
 *   // Fully custom
 *   $grid->setTheme(new Theme(
 *       table: 'my-table striped',
 *       searchInput: 'my-input',
 *       // … all other properties
 *   ));
 */
final class Theme
{
    public function __construct(
        // ── Table ────────────────────────────────────────────────────────────
        public readonly string $tableWrapper    = '',
        public readonly string $table           = '',
        public readonly string $thead           = '',
        public readonly string $th              = '',
        public readonly string $thSortable      = '',
        public readonly string $thSorted        = '',
        public readonly string $thFilterCell    = '',
        public readonly string $td              = '',

        // ── Sort link ─────────────────────────────────────────────────────────
        public readonly string $sortLink        = '',
        public readonly string $sortIconAsc     = '',
        public readonly string $sortIconDesc    = '',
        public readonly string $sortIconNeutral = '',

        // ── Toolbar ───────────────────────────────────────────────────────────
        public readonly string $toolbarWrapper   = '',
        public readonly string $toolbar         = '',
        public readonly string $searchGroup     = '',
        public readonly string $searchInput     = '',
        public readonly string $searchApplyBtn  = '',
        public readonly string $searchResetBtn  = '',

        // ── Filter ────────────────────────────────────────────────────────────
        public readonly string $filterInput      = '',
        public readonly string $filterSelectInput = '',
        public readonly string $filterApplyBtn   = '',
        public readonly string $filterResetBtn   = '',
        public readonly string $filterClearBtn   = '',

        // ── Pagination ────────────────────────────────────────────────────────
        public readonly string $paginationWrapper = '',
        public readonly string $paginationNav   = '',
        public readonly string $paginationList  = '',
        public readonly string $pageItem        = '',
        public readonly string $pageItemActive  = '',
        public readonly string $pageItemDisabled = '',
        public readonly string $pageLink        = '',
        public readonly string $paginationInfo  = '',

        // ── Empty row ─────────────────────────────────────────────────────────
        public readonly string $noDataCell      = '',
        public readonly string $noDataIcon      = '',

        // ── Loading state (added to table while AJAX request is in flight) ────
        public readonly string $loadingClass    = '',

        // ── MultiSelect filter (checkbox dropdown) ────────────────────────────
        public readonly string $filterMsTrigger    = 'phx-grid-ms-trigger',
        public readonly string $filterMsDropdown   = 'phx-grid-ms-dropdown',
        public readonly string $filterMsItem       = 'phx-grid-ms-item',
        public readonly string $filterMsItemActive = 'phx-grid-ms-item-active',
        public readonly string $filterMsCheck      = '',
        public readonly string $filterMsLabel      = '',
        public readonly string $filterMsApplyBtn   = '',

        // ── SelectAjax filter rendered as DropDownList widget ─────────────────
        public readonly string $filterDdlWrapper      = 'dropdownlist-wrapper',
        public readonly string $filterDdlTrigger      = 'dropdownlist-trigger',
        public readonly string $filterDdlDropdown     = 'dropdownlist-dropdown',
        public readonly string $filterDdlFilterRow    = 'dropdownlist-filter',
        public readonly string $filterDdlFilterInput  = 'dropdownlist-filter-input',
        public readonly string $filterDdlItem         = '',
        public readonly string $filterDdlItemActive   = 'dropdownlist-active',
        public readonly string $filterDdlItemSelected = 'dropdownlist-selected',
        public readonly string $filterDdlNoResults    = 'dropdownlist-no-results',
        public readonly string $filterDdlMark         = '',

        // ── Icons (HTML strings, e.g. Bootstrap Icons or Heroicons SVG) ───────
        public readonly string $iconSearch      = '<i class="bi bi-search"></i>',
        public readonly string $iconClear       = '<i class="bi bi-x-lg"></i>',
        public readonly string $iconSortAsc     = '<i class="bi bi-caret-up-fill"></i>',
        public readonly string $iconSortDesc    = '<i class="bi bi-caret-down-fill"></i>',
        public readonly string $iconSortNeutral = '<i class="bi bi-chevron-expand opacity-50"></i>',
        public readonly string $iconApply       = '<i class="bi bi-funnel-fill"></i>',
        public readonly string $iconReset       = '<i class="bi bi-x-circle"></i>',
        public readonly string $iconFirst       = '<i class="bi bi-chevron-double-left"></i>',
        public readonly string $iconPrev        = '<i class="bi bi-chevron-left"></i>',
        public readonly string $iconNext        = '<i class="bi bi-chevron-right"></i>',
        public readonly string $iconLast        = '<i class="bi bi-chevron-double-right"></i>',
        public readonly string $iconEmpty       = '<i class="bi bi-inbox fs-4 d-block mb-1"></i>',

        // ── Action column ─────────────────────────────────────────────────────
        public readonly string $actionPrimaryBtn  = 'btn btn-sm btn-outline-primary',
        public readonly string $actionMenuToggle  = 'btn btn-sm btn-outline-secondary phx-grid-action-toggle',
        public readonly string $actionMenu        = 'phx-grid-action-menu dropdown-menu',
        public readonly string $actionMenuItem    = 'dropdown-item',
        public readonly string $iconActionMenu    = '<i class="bi bi-three-dots-vertical"></i>',

        // ── Column toggle (toolbar dropdown for column visibility) ────────────
        public readonly string $colToggleBtn       = '',
        public readonly string $colToggleDropdown  = '',
        public readonly string $colToggleItem      = '',
        public readonly string $colToggleCheck     = '',
        public readonly string $colToggleLabel     = '',
        public readonly string $iconColumns        = '<i class="bi bi-layout-three-columns"></i>',

        // ── Presets (toolbar preset selector + actions) ───────────────────────
        public readonly string $presetGroup         = '',
        public readonly string $presetSelect        = '',
        public readonly string $presetSaveBtn       = '',
        public readonly string $presetSaveSplitBtn  = '',
        public readonly string $presetDropdown      = '',
        public readonly string $presetDropdownItem  = '',
        public readonly string $presetDeleteItem = '',
        public readonly string $presetDropdownDivider = '',
        public readonly string $iconPresetSave      = '<i class="bi bi-save"></i>',

        // ── Bulk action bar (sticky bar shown when rows are selected) ─────────
        public readonly string $bulkBar             = '',
        public readonly string $bulkBarCount        = '',
        public readonly string $bulkBarBtn          = '',
    ) {
    }

    // ── Named constructors ────────────────────────────────────────────────────

    /**
     * Bare default – only structural phx-grid-* classes, no CSS framework dependency.
     * Use this as a starting point for fully custom themes, or when you handle
     * all styling yourself via global CSS rules targeting the phx-grid-* selectors.
     *
     * Equivalent to: $grid->setTheme(GridControl::DEFAULT)
     */
    public static function default(): self
    {
        return new self(
            // Table
            tableWrapper:       'phx-grid-table-wrapper',
            table:              'phx-grid-table',
            thead:              '',
            th:                 'phx-grid-th',
            thSortable:         'phx-grid-sortable',
            thSorted:           'phx-grid-sorted',
            thFilterCell:       'phx-grid-filter-th',
            td:                 'phx-grid-td',

            // Sort link
            sortLink:           'phx-grid-sort-link',
            sortIconAsc:        'phx-grid-sort-icon',
            sortIconDesc:       'phx-grid-sort-icon',
            sortIconNeutral:    'phx-grid-sort-icon',

            // Toolbar
            toolbar:            'phx-grid-toolbar',
            searchGroup:        'phx-grid-search-group',
            searchInput:        'phx-grid-search-input',
            searchApplyBtn:     'phx-grid-btn phx-grid-btn-apply',
            searchResetBtn:     'phx-grid-btn phx-grid-btn-reset',

            // Filter
            filterInput:        'phx-grid-filter-input',
            filterSelectInput:  'phx-grid-filter-input phx-grid-filter-select',
            filterApplyBtn:     'phx-grid-btn phx-grid-btn-apply',
            filterResetBtn:     'phx-grid-btn phx-grid-btn-reset',

            // Pagination
            paginationNav:      'phx-grid-pagination',
            paginationList:     'phx-grid-page-list',
            pageItem:           'phx-grid-page-item',
            pageItemActive:     'phx-grid-page-active',
            pageItemDisabled:   'phx-grid-page-disabled',
            pageLink:           'phx-grid-page-link',
            paginationInfo:     'phx-grid-pagination-info',

            // Empty
            noDataCell:         'phx-grid-no-data',
            noDataIcon:         '',

            // Loading
            loadingClass:       'phx-grid-loading',

            // Icons – keep Bootstrap Icons as sensible default;
            // override via setTheme([...]) or pass your own HTML strings
        );
    }

    public static function bootstrap(): self
    {
        return new self(
            // Table
            tableWrapper:       'table-responsive',
            table:              'table table-hover table-bordered align-middle phx-grid-table',
            thead:              '',
            th:                 'phx-grid-th',
            thSortable:         'phx-grid-sortable',
            thSorted:           'phx-grid-sorted',
            thFilterCell:       'phx-grid-filter-th p-1',
            td:                 'phx-grid-td',

            // Sort link
            sortLink:           'd-flex align-items-center gap-1 text-decoration-none text-reset phx-grid-sort-link',
            sortIconAsc:        'text-muted small',
            sortIconDesc:       'text-muted small',
            sortIconNeutral:    'text-muted small',

            // Toolbar
            toolbar:            'd-flex align-items-center mb-2',
            searchGroup:        'input-group input-group-sm flex-grow-1',
            searchInput:        'form-control phx-grid-search-input',
            searchApplyBtn:     'btn btn-outline-secondary',
            searchResetBtn:     'btn btn-outline-secondary',

            // Filter
            filterInput:        'form-control form-control-sm phx-grid-filter-input',
            filterSelectInput:  'form-select form-select-sm phx-grid-filter-input',
            filterApplyBtn:     'btn btn-sm btn-outline-primary',
            filterResetBtn:     'btn btn-sm btn-outline-secondary',
            filterClearBtn:     'btn btn-sm btn-link p-0 ms-1 phx-grid-filter-clear',

            // Pagination
            paginationNav:      'd-flex flex-wrap gap-2 align-items-center justify-content-between mt-2',
            paginationList:     'pagination pagination-sm mb-0',
            pageItem:           'page-item',
            pageItemActive:     'active',
            pageItemDisabled:   'disabled',
            pageLink:           'page-link',
            paginationInfo:     'text-muted',

            // Empty
            noDataCell:         'text-center text-muted py-5',
            noDataIcon:         '',

            // Loading
            loadingClass:       'phx-grid-loading',

            // MultiSelect filter
            filterMsTrigger:    'form-select form-select-sm text-start phx-grid-ms-trigger',
            filterMsDropdown:   'dropdown-menu py-1 phx-grid-ms-dropdown',
            filterMsItem:       'dropdown-item d-flex align-items-center gap-2 py-1 phx-grid-ms-item',
            filterMsItemActive: 'active',
            filterMsCheck:      'form-check-input mt-0 flex-shrink-0',
            filterMsLabel:      '',
            filterMsApplyBtn:   'btn btn-sm btn-primary w-100',

            // SelectAjax as DropDownList widget
            filterDdlWrapper:      'dropdownlist-wrapper position-relative d-flex',
            filterDdlTrigger:      'form-select form-select-sm text-start dropdownlist-bs-trigger w-100',
            filterDdlDropdown:     'dropdown-menu w-100 py-0',
            filterDdlFilterRow:    'p-2',
            filterDdlFilterInput:  'form-control form-control-sm',
            filterDdlItem:         'dropdown-item',
            filterDdlItemActive:   'active',
            filterDdlItemSelected: 'fw-semibold',
            filterDdlNoResults:    'dropdown-item disabled text-muted fst-italic',
            filterDdlMark:         'fw-bold bg-transparent p-0',

            // Action column
            actionPrimaryBtn:   'btn btn-sm btn-outline-primary',
            actionMenuToggle:   'btn btn-sm btn-outline-primary dropdown-toggle phx-grid-action-toggle',
            actionMenu:         'phx-grid-action-menu dropdown-menu dropdown-menu-end',
            actionMenuItem:     'dropdown-item',
            iconActionMenu:     '<i class="bi bi-three-dots-vertical"></i>',

            // Column toggle
            colToggleBtn:       'btn btn-sm btn-outline-secondary dropdown-toggle phx-grid-coltoggle-toggle',
            colToggleDropdown:  'phx-grid-coltoggle-menu dropdown-menu dropdown-menu-end p-2',
            colToggleItem:      'd-flex align-items-center gap-2 py-1 px-2 phx-grid-coltoggle-item',
            colToggleCheck:     'form-check-input mt-0 flex-shrink-0',
            colToggleLabel:     '',
            iconColumns:        '<i class="bi bi-layout-three-columns"></i>',

            // Presets
            presetGroup:         'd-flex align-items-center gap-1',
            presetSelect:        'form-select form-select-sm',
            presetSaveBtn:       'btn btn-sm btn-outline-secondary d-flex align-items-center gap-1',
            presetSaveSplitBtn:  'btn btn-sm btn-outline-secondary dropdown-toggle dropdown-toggle-split phx-grid-action-toggle',
            presetDropdown:      'phx-grid-action-menu dropdown-menu dropdown-menu-end',
            presetDropdownItem:  'dropdown-item d-flex align-items-center gap-2',
            presetDeleteItem: 'dropdown-item d-flex align-items-center gap-2',
            presetDropdownDivider: 'dropdown-divider',
            iconPresetSave:      '<i class="bi bi-save"></i>',

            // Bulk action bar
            bulkBar:             'd-flex align-items-center gap-2 px-3 py-2 bg-body-secondary border-bottom',
            bulkBarCount:        'fw-semibold text-muted me-1',
            bulkBarBtn:          'btn btn-sm btn-outline-secondary',
        );
    }

    public static function tailwind(): self
    {
        return new self(
            // Table
            tableWrapper:       'overflow-x-auto',
            table:              'min-w-full divide-y divide-gray-200 phx-grid-table',
            thead:              'bg-gray-50',
            th:                 'px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider phx-grid-th',
            thSortable:         'cursor-pointer select-none phx-grid-sortable',
            thSorted:           'text-gray-900 phx-grid-sorted',
            thFilterCell:       'px-2 py-1 phx-grid-filter-th',
            td:                 'px-4 py-3 text-sm text-gray-900 phx-grid-td',

            // Sort link
            sortLink:           'flex items-center gap-1 hover:text-gray-900 phx-grid-sort-link',
            sortIconAsc:        'text-gray-400 text-xs',
            sortIconDesc:       'text-gray-400 text-xs',
            sortIconNeutral:    'text-gray-300 text-xs',

            // Toolbar
            toolbar:            'flex items-center gap-2 mb-3',
            searchGroup:        'flex flex-1 min-w-0',
            searchInput:        'flex-1 min-w-0 border border-gray-300 rounded-l px-3 py-1.5 text-sm focus:outline-none focus:ring-1 focus:ring-blue-500 phx-grid-search-input',
            searchApplyBtn:     'border border-l-0 border-gray-300 px-3 py-1.5 text-sm bg-white hover:bg-gray-50 whitespace-nowrap',
            searchResetBtn:     'border border-l-0 border-gray-300 rounded-r px-3 py-1.5 text-sm bg-white hover:bg-gray-50 whitespace-nowrap',

            // Filter
            filterInput:        'w-full border border-gray-300 rounded px-2 py-1 text-xs focus:outline-none focus:ring-1 focus:ring-blue-500 phx-grid-filter-input',
            filterSelectInput:  'w-full border border-gray-300 rounded px-2 py-1 text-xs focus:outline-none focus:ring-1 focus:ring-blue-500 phx-grid-filter-input',
            filterApplyBtn:     'inline-flex items-center gap-1 px-3 py-1 text-xs font-medium text-white bg-blue-600 rounded hover:bg-blue-700',
            filterResetBtn:     'inline-flex items-center gap-1 px-3 py-1 text-xs font-medium text-gray-700 bg-white border border-gray-300 rounded hover:bg-gray-50',
            filterClearBtn:     'absolute right-1 top-1/2 -translate-y-1/2 p-0.5 text-gray-400 hover:text-gray-600 phx-grid-filter-clear',

            // Pagination
            paginationNav:      'flex flex-wrap gap-2 items-center justify-between mt-3',
            paginationList:     'flex gap-1',
            pageItem:           '',
            pageItemActive:     '',
            pageItemDisabled:   'pointer-events-none opacity-50',
            pageLink:           'px-3 py-1 text-sm border border-gray-300 rounded hover:bg-gray-50',
            paginationInfo:     'text-gray-500',

            // Empty
            noDataCell:         'text-center text-gray-400 py-12',
            noDataIcon:         '',

            // Loading
            loadingClass:       'phx-grid-loading',

            // MultiSelect filter
            filterMsTrigger:    'flex items-center justify-between w-full rounded border border-gray-300 py-1 pl-2 pr-2 text-sm cursor-pointer select-none focus:outline-none focus:ring-1 focus:ring-indigo-500 dark:bg-gray-800 dark:border-gray-600 dark:text-gray-100 phx-grid-ms-trigger',
            filterMsDropdown:   'absolute z-50 mt-1 w-full rounded-md bg-white shadow-lg ring-1 ring-black ring-opacity-5 py-1 max-h-60 overflow-y-auto dark:bg-gray-800 dark:ring-gray-700 phx-grid-ms-dropdown',
            filterMsItem:       'flex items-center gap-2 px-3 py-1.5 text-sm cursor-pointer text-gray-700 hover:bg-gray-100 dark:text-gray-200 dark:hover:bg-gray-700 phx-grid-ms-item',
            filterMsItemActive: 'bg-indigo-50 dark:bg-indigo-900/40',
            filterMsCheck:      'rounded border-gray-300 text-indigo-600 flex-shrink-0',
            filterMsLabel:      '',
            filterMsApplyBtn:   'w-full px-3 py-1.5 text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 rounded',

            // SelectAjax as DropDownList widget
            filterDdlWrapper:      'relative',
            filterDdlTrigger:      'flex items-center justify-between w-full rounded-md border border-gray-300 py-1.5 pl-3 pr-3 text-sm cursor-pointer select-none focus:outline-none focus:ring-1 focus:ring-indigo-500 dark:bg-gray-800 dark:border-gray-600 dark:text-gray-100',
            filterDdlDropdown:     'absolute z-50 mt-1 w-full rounded-md bg-white shadow-lg ring-1 ring-black ring-opacity-5 dark:bg-gray-800 dark:ring-gray-700',
            filterDdlFilterRow:    'p-2 border-b border-gray-200 dark:border-gray-700',
            filterDdlFilterInput:  'block w-full rounded border border-gray-300 bg-white py-1 px-2 text-sm placeholder:text-gray-400 focus:outline-none focus:ring-1 focus:ring-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-100',
            filterDdlItem:         'relative cursor-pointer select-none px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 dark:text-gray-200 dark:hover:bg-gray-700',
            filterDdlItemActive:   'bg-indigo-50 text-indigo-900 dark:bg-indigo-900/40 dark:text-indigo-100',
            filterDdlItemSelected: 'font-semibold',
            filterDdlNoResults:    'px-4 py-2 text-sm italic text-gray-400 dark:text-gray-500',
            filterDdlMark:         'bg-transparent font-bold text-indigo-600 dark:text-indigo-400',

            // Tailwind uses Heroicons – override icon HTML
            iconSearch:         '<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>',
            iconClear:          '<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>',
            iconSortAsc:        '<svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20"><path d="M5 10l5-5 5 5H5z"/></svg>',
            iconSortDesc:       '<svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20"><path d="M5 10l5 5 5-5H5z"/></svg>',
            iconSortNeutral:    '<svg class="w-3 h-3 opacity-40" fill="currentColor" viewBox="0 0 20 20"><path d="M5 8l5-5 5 5H5zm0 4l5 5 5-5H5z"/></svg>',
            iconApply:          '<svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2a1 1 0 01-.293.707L13 13.414V19a1 1 0 01-.553.894l-4-2A1 1 0 018 17v-3.586L3.293 6.707A1 1 0 013 6V4z"/></svg>',
            iconReset:          '<svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>',
            iconFirst:          '<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 19l-7-7 7-7m8 14l-7-7 7-7"/></svg>',
            iconPrev:           '<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>',
            iconNext:           '<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>',
            iconLast:           '<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 5l7 7-7 7M5 5l7 7-7 7"/></svg>',
            iconEmpty:          '<svg class="w-10 h-10 mx-auto mb-2 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"/></svg>',

            // Action column
            actionPrimaryBtn:   'inline-flex items-center gap-1 px-2 py-1 text-xs font-medium text-indigo-700 border border-indigo-300 rounded hover:bg-indigo-50 dark:text-indigo-300 dark:border-indigo-700',
            actionMenuToggle:   'inline-flex items-center px-1.5 py-1 text-gray-500 border border-gray-300 rounded hover:bg-gray-50 dark:text-gray-400 dark:border-gray-600 phx-grid-action-toggle',
            actionMenu:         'phx-grid-action-menu absolute right-0 z-50 mt-1 min-w-max rounded-md bg-white shadow-lg ring-1 ring-black ring-opacity-5 py-1 dark:bg-gray-800 dark:ring-gray-700',
            actionMenuItem:     'block w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 dark:text-gray-200 dark:hover:bg-gray-700',
            iconActionMenu:     '<svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20"><path d="M10 6a2 2 0 110-4 2 2 0 010 4zm0 6a2 2 0 110-4 2 2 0 010 4zm0 6a2 2 0 110-4 2 2 0 010 4z"/></svg>',

            // Column toggle
            colToggleBtn:       'inline-flex items-center gap-1 px-3 py-1.5 text-sm font-medium border border-gray-300 rounded hover:bg-gray-50 dark:border-gray-600 dark:hover:bg-gray-700 phx-grid-coltoggle-toggle',
            colToggleDropdown:  'absolute right-0 z-50 mt-1 min-w-max rounded-md bg-white shadow-lg ring-1 ring-black ring-opacity-5 p-2 dark:bg-gray-800 dark:ring-gray-700 phx-grid-coltoggle-menu',
            colToggleItem:      'flex items-center gap-2 px-2 py-1 text-sm cursor-pointer text-gray-700 hover:bg-gray-100 rounded dark:text-gray-200 dark:hover:bg-gray-700 phx-grid-coltoggle-item',
            colToggleCheck:     'rounded border-gray-300 text-indigo-600 flex-shrink-0',
            colToggleLabel:     '',
            iconColumns:        '<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17V7m0 10a2 2 0 01-2 2H5a2 2 0 01-2-2V7a2 2 0 012-2h2a2 2 0 012 2m0 10a2 2 0 002 2h2a2 2 0 002-2M9 7a2 2 0 012-2h2a2 2 0 012 2m0 10V7"/></svg>',

            // Presets
            presetGroup:         'flex items-center gap-1',
            presetSelect:        'block rounded border border-gray-300 py-1 pl-2 pr-7 text-sm focus:outline-none focus:ring-1 focus:ring-indigo-500 dark:bg-gray-800 dark:border-gray-600 dark:text-gray-100',
            presetSaveBtn:       'inline-flex items-center gap-1 px-3 py-1.5 text-sm font-medium border border-gray-300 rounded-l hover:bg-gray-50 dark:border-gray-600 dark:hover:bg-gray-700',
            presetSaveSplitBtn:  'inline-flex items-center px-1.5 py-1.5 text-sm border border-l-0 border-gray-300 rounded-r hover:bg-gray-50 dark:border-gray-600 dark:hover:bg-gray-700 phx-grid-action-toggle',
            presetDropdown:      'phx-grid-action-menu absolute right-0 z-50 mt-1 min-w-max rounded-md bg-white shadow-lg ring-1 ring-black ring-opacity-5 py-1 dark:bg-gray-800 dark:ring-gray-700',
            presetDropdownItem:  'block w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 dark:text-gray-200 dark:hover:bg-gray-700',
            presetDeleteItem: 'block w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 dark:text-gray-200 dark:hover:bg-gray-700',
            presetDropdownDivider: 'border-t border-gray-200 dark:border-gray-700 my-1',
            iconPresetSave:      '<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3 3m0 0l-3-3m3 3V4"/></svg>',

            // Bulk action bar
            bulkBar:             'flex items-center gap-2 px-3 py-2 bg-gray-100 border-b border-gray-200 dark:bg-gray-800 dark:border-gray-700',
            bulkBarCount:        'font-semibold text-gray-500 dark:text-gray-400',
            bulkBarBtn:          'inline-flex items-center gap-1 px-2 py-1 text-xs font-medium text-gray-700 border border-gray-300 rounded hover:bg-gray-200 dark:text-gray-300 dark:border-gray-600 dark:hover:bg-gray-700',
        );
    }
}
