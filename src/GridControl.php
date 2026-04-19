<?php declare(strict_types=1);

namespace Phlox\Components\Grid;

use Phlox\Components\Grid\Column\ActionColumn;
use Phlox\Components\Grid\Column\BadgeColumn;
use Phlox\Components\Grid\Column\BoolColumn;
use Phlox\Components\Grid\Column\Column;
use Phlox\Components\Grid\Column\DateColumn;
use Phlox\Components\Grid\Column\NumberColumn;
use Phlox\Components\Grid\Column\TextColumn;
use Phlox\Components\Grid\DataSource\NetteDbDataSource;
use Nette\Application\UI\Control;
use Phlox\Components\Grid\Filter\FilterType;
use Phlox\Components\Grid\Theme\Theme;
use Nette\Database\Table\Selection;
use Nette\Utils\Paginator;

/**
 * GridControl – Nette UI Control
 *
 * State flow:
 *   Signal (POST or GET link) → handler saves state → redirect('this')
 *   → saveState() bakes state into URL → browser GET (no do=) → loadState()
 *   → F5 safe
 *
 * AJAX (Naja):
 *   POST signal → PHP sends { postGet: true, url: '...' }
 *   → JS makes second AJAX GET to url → snippets redrawn → URL updated
 *
 * @property-read \Nette\Bridges\ApplicationLatte\Template $template
 */
class GridControl extends Control
{
    public const DEFAULT   = 'default';
    public const BOOTSTRAP = 'bootstrap';
    public const TAILWIND  = 'tailwind';

    // ── Persistent state ─────────────────────────────────────────────────────

    private string $orderBy = '';
    private int    $page    = 1;
    private string $search  = '';

    /** @var array<string, string> non-empty values only */
    private array $filters = [];

    /** @var array<string, list<string>> multi-select filter values (key => [val, …]) */
    private array $multiFilters = [];

    // ── Configuration ────────────────────────────────────────────────────────

    private ?Selection $selection      = null;
    private int        $itemsPerPage   = 20;
    private bool       $showToolbar    = true;
    private bool       $showSearch     = true;
    private bool       $showPagination = true;
    private bool       $ajaxEnabled    = false;
    private bool       $resizable      = false;
    private bool       $reorderable    = false;
    private bool       $showColumnToggle = false;
    private string     $defaultOrderBy = '';
    private Theme      $theme;

    /** @var list<int>|null  null = selector hidden */
    private ?array $perPageOptions = null;

    /** @var array<string, string> */
    private array $texts = [];

    /** @var Column[] */
    private array $columns = [];

    /** @var \Closure|null */
    private ?\Closure $rowCallback = null;

    // ── Bulk actions & selection ──────────────────────────────────────────────

    private ?string $primaryKey = null;
    private bool    $showCheckboxes = false;

    /** @var 'auto'|'fixed' */
    private string  $tableLayout    = 'auto';

    /** @var BulkAction[] */
    private array $bulkActions = [];

    // ── Presets ───────────────────────────────────────────────────────────────

    /** @var GridPreset[]|null  null = presets UI hidden */
    private ?array $presets = null;

    /** @var bool  whether presets UI is enabled */
    private bool $showPresets = false;

    /** @var \Closure|null  fn(): GridPreset[]  — called before each render for fresh data */
    private ?\Closure $onPresetsLoad = null;

    /** Active preset ID (persistent URL parameter) */
    private string $activePreset = '';

    /** @var \Closure|null fn(string $presetId, array $config): void */
    private ?\Closure $onPresetSave = null;

    /** @var \Closure|null fn(string $name, array $config): string|int|null — return new preset ID */
    private ?\Closure $onPresetSaveAs = null;

    /** @var \Closure|null fn(string $presetId, string $newName): void */
    private ?\Closure $onPresetRename = null;

    /** @var \Closure|null fn(string $presetId): void */
    private ?\Closure $onPresetDefault = null;

    /** @var \Closure|null fn(string $presetId): void */
    private ?\Closure $onPresetDelete = null;

    // ── Fluent API ───────────────────────────────────────────────────────────

    public function __construct()
    {
        $this->theme = Theme::bootstrap();
    }

    public function setDataSource(Selection $selection): static
    {
        $this->selection = $selection;
        return $this;
    }

    public function setItemsPerPage(int $count): static
    {
        $this->itemsPerPage = max(1, $count);
        return $this;
    }

    /**
     * Set the CSS theme.
     *
     * Pass a constant:
     *   $grid->setTheme(GridControl::BOOTSTRAP);   // Bootstrap 5 (default)
     *   $grid->setTheme(GridControl::TAILWIND);    // Tailwind CSS
     *   $grid->setTheme(GridControl::DEFAULT);     // bare phx-grid-* classes only, no framework
     *
     * Pass an array to override specific classes (Bootstrap is used as base):
     *   $grid->setTheme([
     *       'table'       => 'table table-sm table-striped',
     *       'searchInput' => 'form-control form-control-sm my-custom-search',
     *   ]);
     *
     * Array keys correspond to Theme constructor parameter names.
     * Only the keys you provide are overridden; the rest keeps the Bootstrap preset values.
     *
     * @param string|array<string, string> $theme
     */
    public function setTheme(string|array $theme): static
    {
        if (is_array($theme)) {
            // Merge overrides into Bootstrap preset (same pattern as ComboBoxInput)
            $base = get_object_vars(Theme::bootstrap());
            $this->theme = new Theme(...array_merge($base, $theme));
        } else {
            $this->theme = match ($theme) {
                self::TAILWIND => Theme::tailwind(),
                self::DEFAULT  => Theme::default(),
                default        => Theme::bootstrap(),
            };
        }
        return $this;
    }

    public function setNoDataMessage(string $message): static
    {
        return $this->setTexts(['noData' => $message]);
    }

    /**
     * Override UI text strings – useful for localisation or custom wording.
     *
     * Pass only the keys you want to change; omitted keys keep their defaults.
     *
     * Available keys:
     *   noData       — "Žádné záznamy."
     *   search       — placeholder vyhledávacího pole
     *   apply        — popisek tlačítka Použít
     *   reset        — popisek odkazu Zrušit
     *   filter       — placeholder textového filtru sloupce
     *   ajaxFilter   — placeholder SelectAjax filtru
     *   selectAll    — první položka select filtrů ("— vše —")
     *   yes / no     — možnosti bool filtru
     *   checkAll     — title master checkboxu
     *   bulkActions  — title tlačítka hromadných akcí
     *   dateFrom     — popisek "Od:" u datumového rozsahu
     *   dateTo       — popisek "Do:" u datumového rozsahu
     *   numMin       — popisek "Min:" u číselného rozsahu
     *   numMax       — popisek "Max:" u číselného rozsahu
     *
     * @param array<string, string> $texts
     */
    public function setTexts(array $texts): static
    {
        $this->texts = array_merge($this->texts, $texts);
        return $this;
    }

    public function showToolbar(bool $show = true): static
    {
        $this->showToolbar = $show;
        return $this;
    }

    public function showSearch(bool $show = true): static
    {
        $this->showSearch = $show;
        return $this;
    }

    public function showPagination(bool $show = true): static
    {
        $this->showPagination = $show;
        return $this;
    }

    /**
     * Show a per-page selector next to pagination.
     *
     * Pass an array of allowed values — the current itemsPerPage will be used
     * as the selected value. If it is not in the list, it is added automatically.
     *
     *   $grid->setPerPageOptions([10, 25, 50, 100, 250]);
     *
     * Pass null (default) to hide the selector.
     *
     * @param list<int>|null $options
     */
    public function setPerPageOptions(?array $options): static
    {
        $this->perPageOptions = $options;
        return $this;
    }

    public function useAjax(bool $enabled = true): static
    {
        $this->ajaxEnabled = $enabled;
        return $this;
    }

    public function setResizable(bool $enabled = true): static
    {
        $this->resizable = $enabled;
        return $this;
    }

    public function setReorderable(bool $enabled = true): static
    {
        $this->reorderable = $enabled;
        return $this;
    }

    public function showColumnToggle(bool $enabled = true): static
    {
        $this->showColumnToggle = $enabled;
        return $this;
    }

    // ── Presets fluent API ────────────────────────────────────────────────────

    /**
     * Enable or disable the presets panel in the toolbar.
     */
    public function showPresets(bool $enabled = true): static
    {
        $this->showPresets = $enabled;
        return $this;
    }

    /**
     * Set the callback that loads presets before each render.
     *
     * Called lazily in render() — always returns fresh data, even after
     * a signal handler modifies the DB.
     *
     *   $grid->showPresets();
     *   $grid->onPresetsLoad(fn() => $this->presetRepo->findByUser($userId));
     *
     * @param \Closure $callback  fn(): GridPreset[]
     */
    public function onPresetsLoad(\Closure $callback): static
    {
        $this->onPresetsLoad = $callback;
        $this->showPresets = true; // convenience: registering a loader implies show
        return $this;
    }

    /** Callback: fn(string $presetId, array $config): void */
    public function onPresetSave(\Closure $callback): static
    {
        $this->onPresetSave = $callback;
        return $this;
    }

    /** Callback: fn(string $name, array $config): string|int|null — return new preset ID */
    public function onPresetSaveAs(\Closure $callback): static
    {
        $this->onPresetSaveAs = $callback;
        return $this;
    }

    /** Callback: fn(string $presetId, string $newName): void */
    public function onPresetRename(\Closure $callback): static
    {
        $this->onPresetRename = $callback;
        return $this;
    }

    /** Callback: fn(string $presetId): void */
    public function onPresetDefault(\Closure $callback): static
    {
        $this->onPresetDefault = $callback;
        return $this;
    }

    /** Callback: fn(string $presetId): void */
    public function onPresetDelete(\Closure $callback): static
    {
        $this->onPresetDelete = $callback;
        return $this;
    }

    public function setDefaultSort(string $columnKey, string $dir = 'asc'): static
    {
        $this->defaultOrderBy = $columnKey . ' ' . (strtolower($dir) === 'desc' ? 'desc' : 'asc');
        return $this;
    }

    /**
     * Set table-layout CSS property.
     *
     *   'auto'  (default) — column widths adapt to content
     *   'fixed' — column widths are taken from <col> / first row; content is clipped
     *             with text-overflow: ellipsis. Use width() on columns to control sizing.
     *
     * @param 'auto'|'fixed' $layout
     */
    public function setTableLayout(string $layout): static
    {
        $this->tableLayout = $layout === 'fixed' ? 'fixed' : 'auto';
        return $this;
    }

    /**
     * The callback receives the row and its 0-based index on the current page.
     * It can return:
     *   - a string  → used as the class attribute value (BC compatible)
     *   - an array  → key/value HTML attributes, e.g. ['class' => 'table-danger', 'data-id' => $row['id']]
     *
     * Examples:
     *   // Simple class (string)
     *   $grid->setRowCallback(fn($row) => $row['active'] ? '' : 'table-secondary text-muted');
     *
     *   // Full attributes (array), with row index for zebra striping
     *   $grid->setRowCallback(fn($row, int $index) => [
     *       'class'       => $index % 2 === 0 ? 'row-even' : 'row-odd',
     *       'data-id'     => $row['id'],
     *       'data-status' => $row['status'],
     *   ]);
     */
    public function setRowCallback(\Closure $callback): static
    {
        $this->rowCallback = $callback;
        return $this;
    }

    /**
     * Render HTML attribute string for a table row from the rowCallback result.
     * Handles both string (class only) and array (full attributes) return values.
     *
     * @internal Used by the Latte template.
     */
    public function renderRowAttrs(mixed $row, int $index): array
    {
        if ($this->rowCallback === null) {
            return [];
        }

        $result = ($this->rowCallback)($row, $index);

        if (is_string($result)) {
            return $result !== '' ? ['class' => $result] : [];
        }

        if (is_array($result)) {
            return array_filter($result, fn($v) => $v !== null && $v !== false);
        }

        return [];
    }

    /**
     * Set the primary key column — required for bulk actions and row checkboxes.
     *
     * @param bool $showCheckboxes  Force checkboxes visible even without bulk actions.
     */
    public function setPrimaryKey(string $column, bool $showCheckboxes = false): static
    {
        $this->primaryKey     = $column;
        $this->showCheckboxes = $showCheckboxes;
        return $this;
    }

    /**
     * Show row checkboxes without defining any bulk actions.
     * Useful when you want to read selected IDs via JS.
     * Requires setPrimaryKey() to be called first.
     */
    public function showCheckboxes(bool $show = true): static
    {
        $this->showCheckboxes = $show;
        return $this;
    }

    /**
     * Add a bulk action available in the grid bulk action bar.
     *
     * @param string   $id         Unique identifier
     * @param string   $caption    Label text
     * @param \Closure $callback   fn(array $ids): void
     * @param string   $icon       CSS icon class (e.g. 'bi bi-trash')
     * @param string   $extraClass Extra CSS classes for the button
     * @param ?string  $confirm    Confirm dialog message (null = no confirm)
     */
    public function addBulkAction(
        string   $id,
        string   $caption,
        \Closure $callback,
        string   $icon       = '',
        string   $extraClass = '',
        ?string  $confirm    = null,
    ): static {
        $this->bulkActions[$id] = new BulkAction($id, $caption, $callback, $icon, $extraClass, $confirm);
        return $this;
    }

    // ── Column factories ─────────────────────────────────────────────────────

    public function addTextColumn(string $key, string $label, ?string $dbColumn = null): TextColumn
    {
        return $this->addColumn(new TextColumn($key, $label, $dbColumn ?? $key));
    }

    public function addDateColumn(string $key, string $label, string $format = 'd.m.Y', ?string $dbColumn = null): DateColumn
    {
        return $this->addColumn(new DateColumn($key, $label, $dbColumn ?? $key, $format));
    }

    /** @param array<scalar, array{label: string, color: string}> $map */
    public function addBadgeColumn(string $key, string $label, array $map = [], ?string $dbColumn = null): BadgeColumn
    {
        return $this->addColumn(new BadgeColumn($key, $label, $dbColumn ?? $key, $map));
    }

    public function addNumberColumn(string $key, string $label, int $decimals = 0, ?string $dbColumn = null): NumberColumn
    {
        return $this->addColumn(new NumberColumn($key, $label, $dbColumn ?? $key, $decimals));
    }

    public function addBoolColumn(string $key, string $label, ?string $dbColumn = null): BoolColumn
    {
        return $this->addColumn(new BoolColumn($key, $label, $dbColumn ?? $key));
    }

    public function addActionColumn(string $key = 'actions', string $label = 'Actions'): ActionColumn
    {
        return $this->addColumn(new ActionColumn($key, $label));
    }

    /** @template T of Column @param T $column @return T */
    public function addColumn(Column $column): Column
    {
        $this->columns[$column->getKey()] = $column;
        return $column;
    }

    // ── State persistence ────────────────────────────────────────────────────

    public function loadState(array $params): void
    {
        parent::loadState($params);
        $this->orderBy = (string) ($params['orderBy'] ?? '');
        $this->page    = max(1, (int) ($params['page'] ?? 1));
        $this->search  = (string) ($params['search'] ?? '');
        $this->activePreset = (string) ($params['preset'] ?? '');
        if (isset($params['perPage']) && $this->perPageOptions !== null) {
            $val = (int) $params['perPage'];
            if (in_array($val, $this->perPageOptions, true)) {
                $this->itemsPerPage = $val;
            }
        }
        $raw = is_array($params['filters'] ?? null) ? $params['filters'] : [];
        $this->filters = array_filter(array_map('strval', $raw), fn(string $v) => $v !== '');

        $rawMulti = is_array($params['mfilters'] ?? null) ? $params['mfilters'] : [];
        $this->multiFilters = [];
        foreach ($rawMulti as $key => $vals) {
            if (!is_array($vals)) continue;
            $cleaned = array_values(array_filter(array_map('strval', $vals), fn(string $v) => $v !== ''));
            if ($cleaned !== []) {
                $this->multiFilters[(string) $key] = $cleaned;
            }
        }

        // Resolve default preset early so its ID appears in all generated URLs
        // (saveState runs before render, so we must set activePreset here).
        if ($this->activePreset === '' && $this->showPresets && $this->onPresetsLoad !== null) {
            $this->presets = ($this->onPresetsLoad)();
            foreach ($this->presets as $p) {
                if ($p->isDefault) {
                    $this->activePreset = $p->id;
                    $this->applyPresetState($p, force: false);
                    break;
                }
            }
        }
    }

    public function saveState(array &$params): void
    {
        parent::saveState($params);
        $params['orderBy'] = $this->orderBy !== '' ? $this->orderBy : null;
        $params['page']    = $this->page > 1 ? $this->page : null;
        $params['search']  = $this->search !== '' ? $this->search : null;
        $params['preset']  = $this->activePreset !== '' ? $this->activePreset : null;
        $params['perPage'] = $this->perPageOptions !== null ? $this->itemsPerPage : null;
        $nonEmpty = array_filter($this->filters, fn(string $v) => $v !== '');
        $params['filters']  = $nonEmpty !== [] ? $nonEmpty : null;
        $params['mfilters'] = $this->multiFilters !== [] ? $this->multiFilters : null;
    }

    // ── Signal handlers ──────────────────────────────────────────────────────

    public function handleSort(string $orderBy): void
    {
        $parts = explode(' ', trim($orderBy), 2);
        $key   = $parts[0] ?? '';
        $dir   = strtolower($parts[1] ?? 'asc');

        if ($dir === 'none') {
            // Third click – clear sort entirely
            $this->orderBy = '';
        } elseif (isset($this->columns[$key]) && $this->columns[$key]->isSortable()) {
            $this->orderBy = "{$key} " . ($dir === 'desc' ? 'desc' : 'asc');
        }
        $this->page = 1;
        $this->finish();
    }

    public function handlePage(int $page): void
    {
        $this->page = max(1, $page);
        $this->finish();
    }

    public function handlePerPage(string $perPage): void
    {
        $val = (int) $perPage;
        if ($this->perPageOptions !== null && in_array($val, $this->perPageOptions, true)) {
            $this->itemsPerPage = $val;
        }
        $this->page = 1;
        $this->finish();
    }

    public function handleFilter(): void
    {
        $post = $this->getPresenter()->getHttpRequest()->getPost();
        $this->search  = trim((string) ($post['search'] ?? ''));
        $raw = (array) ($post['filters'] ?? []);
        $this->filters = array_filter(array_map('strval', $raw), fn(string $v) => $v !== '');

        $rawMulti = (array) ($post['mfilters'] ?? []);
        $this->multiFilters = [];
        foreach ($rawMulti as $key => $vals) {
            if (!is_array($vals)) continue;
            $cleaned = array_values(array_filter(array_map('strval', $vals), fn(string $v) => $v !== ''));
            if ($cleaned !== []) {
                $this->multiFilters[(string) $key] = $cleaned;
            }
        }

        $this->page = 1;
        $this->finish();
    }

    public function handleBulkAction(string $action): void
    {
        if (!isset($this->bulkActions[$action])) {
            $this->finish();
            return;
        }

        $post = $this->getPresenter()->getHttpRequest()->getPost();
        $ids  = array_values(array_filter((array) ($post['_selected'] ?? [])));

        if ($ids !== []) {
            ($this->bulkActions[$action]->callback)($ids);
        }

        $this->finish();
    }

    public function handleReset(): void
    {
        $this->orderBy      = '';
        $this->filters      = [];
        $this->multiFilters = [];
        $this->search       = '';
        $this->page         = 1;
        $this->activePreset = '_none';
        $this->finish();
    }

    // ── Preset signal handlers ───────────────────────────────────────────────

    /** Switch active preset (GET link). */
    public function handlePreset(string $id): void
    {
        $this->activePreset = $id;
        $this->page = 1;

        // Apply sort/filters/search/perPage from the preset
        $preset = $this->findPresetById($id);
        if ($preset !== null) {
            $this->applyPresetState($preset);
        }

        $this->finish();
    }

    /** Save current layout to existing preset. JS POSTs preset ID + config JSON. */
    public function handlePresetSave(): void
    {
        $post = $this->getPresenter()->getHttpRequest()->getPost();
        $presetId = $this->resolvePostedPresetId($post);
        $config = $this->readPostedConfig();
        if ($presetId !== null && $this->onPresetSave !== null) {
            ($this->onPresetSave)($presetId, $config);
        }
        $this->finish();
    }

    /** Save current layout as a new preset. JS POSTs name + config JSON. */
    public function handlePresetSaveAs(): void
    {
        $post = $this->getPresenter()->getHttpRequest()->getPost();
        $name   = trim((string) ($post['_presetName'] ?? ''));
        $config = $this->readPostedConfig();
        if ($name !== '' && $this->onPresetSaveAs !== null) {
            $newId = ($this->onPresetSaveAs)($name, $config);
            if ($newId !== null) {
                $this->activePreset = (string) $newId;
            }
        }
        $this->finish();
    }

    /** Rename active preset. JS POSTs preset ID + new name. */
    public function handlePresetRename(): void
    {
        $post = $this->getPresenter()->getHttpRequest()->getPost();
        $presetId = $this->resolvePostedPresetId($post);
        $name = trim((string) ($post['_presetName'] ?? ''));
        if ($presetId !== null && $name !== '' && $this->onPresetRename !== null) {
            ($this->onPresetRename)($presetId, $name);
        }
        $this->finish();
    }

    /** Set active preset as default. JS POSTs preset ID. */
    public function handlePresetDefault(): void
    {
        $post = $this->getPresenter()->getHttpRequest()->getPost();
        $presetId = $this->resolvePostedPresetId($post);
        if ($presetId !== null && $this->onPresetDefault !== null) {
            ($this->onPresetDefault)($presetId);
        }
        $this->finish();
    }

    /** Delete active preset. JS POSTs preset ID. */
    public function handlePresetDelete(): void
    {
        $post = $this->getPresenter()->getHttpRequest()->getPost();
        $presetId = $this->resolvePostedPresetId($post);
        if ($presetId !== null && $this->onPresetDelete !== null) {
            ($this->onPresetDelete)($presetId);
        }
        $this->activePreset = '';
        $this->finish();
    }

    /**
     * Read preset ID from POST (_presetId), fall back to URL state.
     * Returns null if no valid preset is identified.
     */
    private function resolvePostedPresetId(array $post): ?string
    {
        $id = (string) ($post['_presetId'] ?? $this->activePreset);
        return ($id !== '' && $id !== '_none') ? $id : null;
    }

    /**
     * Read the layout config POSTed by JS.
     * @return array{columnOrder: list<string>, columnWidths: array<string,string>, hiddenColumns: list<string>}
     */
    private function readPostedConfig(): array
    {
        $post = $this->getPresenter()->getHttpRequest()->getPost();
        $json = (string) ($post['_presetConfig'] ?? '{}');
        $raw  = json_decode($json, true);
        if (!is_array($raw)) {
            $raw = [];
        }
        return [
            // Column layout (from JS DOM state)
            'columnOrder'   => array_values(array_filter((array) ($raw['columnOrder'] ?? []), 'is_string')),
            'columnWidths'  => array_filter((array) ($raw['columnWidths'] ?? []), 'is_string'),
            'hiddenColumns' => array_values(array_filter((array) ($raw['hiddenColumns'] ?? []), 'is_string')),

            // Grid state (from current server-side persistent state)
            'orderBy'      => $this->orderBy !== '' ? $this->orderBy : null,
            'search'       => $this->search !== '' ? $this->search : null,
            'filters'      => $this->filters !== [] ? $this->filters : null,
            'multiFilters' => $this->multiFilters !== [] ? $this->multiFilters : null,
            'itemsPerPage' => $this->itemsPerPage,
        ];
    }

    /**
     * Finish a signal.
     *
     * Non-AJAX: standard redirect('this') → saveState bakes state into URL.
     *
     * AJAX: browsers strip X-Requested-With on 302 redirect, so a plain
     * redirect would return HTML instead of JSON. Instead we send
     * { postGet: true, url } and let JS make the second AJAX GET itself.
     */
    public function handleSuggest(string $column, string $query, bool $skipMinChars = false): void
    {
        $presenter = $this->getPresenter();

        if (!isset($this->columns[$column])) {
            $presenter->sendJson([]);
            return;
        }

        $col    = $this->columns[$column];
        $filter = $col->getFilter();

        if ($filter === null || $filter->type !== FilterType::SelectAjax || $filter->selection === null) {
            $presenter->sendJson([]);
            return;
        }

        $query = trim($query);
        if (!$skipMinChars && strlen($query) < $filter->minChars) {
            $presenter->sendJson([]);
            return;
        }

        $results = (clone $filter->selection)
            ->where("{$filter->textColumn} LIKE ?", "%$query%")
            ->limit(50)
            ->fetchAll();

        $items = array_map(fn($row) => [
            'value' => (string) $row[$filter->valueColumn],
            'label' => (string) $row[$filter->textColumn],
        ], $results);

        $presenter->sendJson(array_values($items));
    }

    /**
     * AJAX suggest for SelectAjax filter rendered as DropDownList widget.
     * DropDownList JS reads data-q-param from the trigger element and uses
     * that as the query parameter name — here "{uniqueId}-q" (e.g. "grid-q"),
     * which Nette maps correctly as component parameter $q.
     */
    public function handleSuggestDdl(string $column, string $q = ''): void
    {
        $this->handleSuggest($column, $q, skipMinChars: true);
    }

    /**
     * For SelectAjax columns that have an active filter value, resolve the
     * display label so the template can show "Czech Republic" instead of "42".
     *
     * @return array<string, string>  columnKey => label
     */
    private function resolveFilterLabels(): array
    {
        $labels = [];
        foreach ($this->columns as $key => $col) {
            $filter = $col->getFilter();
            if ($filter === null || $filter->type !== FilterType::SelectAjax) {
                continue;
            }
            $value = $this->filters[$key] ?? '';
            if ($value === '' || $filter->selection === null) {
                continue;
            }
            $row = (clone $filter->selection)
                ->where("{$filter->valueColumn} = ?", $value)
                ->fetch();
            if ($row !== null) {
                $labels[$key] = (string) $row[$filter->textColumn];
            }
        }
        return $labels;
    }

    /**
     * Finish a signal: redirect on non-AJAX, or send postGet payload for AJAX (Naja).
     */
    private function finish(): void
    {
        $presenter = $this->getPresenter();

        if ($presenter->isAjax()) {
            // Call redrawControl() HERE, in the signal handler – not in render().
            // Nette decides "return JSON snippets vs full HTML" based on whether
            // any redrawControl() has been called at signal-handling time.
            // If we only call it in render(), it's too late.
            $this->redrawControl('toolbar');
            $this->redrawControl('grid');
            $this->redrawControl('pagination');

            // Naja's HistoryHandler reads postGet+url and updates the browser URL.
            $presenter->payload->postGet = true;
            $presenter->payload->url     = $presenter->link('this');
            return;
        }

        $presenter->redirect('this');
    }

    // ── Helpers ──────────────────────────────────────────────────────────────

    /**
     * @return array<string, string>
     */
    private function resolveTexts(): array
    {
        return array_merge([
            'noData'      => 'Žádné záznamy.',
            'search'      => 'Hledat…',
            'apply'       => 'Použít',
            'reset'       => 'Zrušit',
            'filter'      => 'Filtrovat…',
            'ajaxFilter'  => 'Začněte psát…',
            'selectAll'   => '— vše —',
            'yes'         => 'Ano',
            'no'          => 'Ne',
            'checkAll'    => 'Vybrat / zrušit vše',
            'bulkSelected' => '{count} vybráno',
            'dateFrom'    => 'Od:',
            'dateTo'      => 'Do:',
            'numMin'      => 'Min:',
            'numMax'      => 'Max:',
            'perPage'     => 'Záznamů na stránku:',
            'columns'     => 'Sloupce',
            'presetNone'  => '— Předvolby —',
            'presetSave'  => 'Uložit',
            'presetSaveAs' => 'Uložit jako…',
            'presetRename' => 'Přejmenovat…',
            'presetSetDefault' => 'Nastavit jako výchozí',
            'presetDelete' => 'Smazat',
            'presetDeleteConfirm' => 'Opravdu smazat tuto předvolbu?',
            'presetNamePrompt' => 'Název předvolby:',
            'presetRenamePrompt' => 'Nový název:',
        ], $this->texts);
    }

    private function parseOrderBy(): array
    {
        $ob = $this->orderBy !== '' ? $this->orderBy : $this->defaultOrderBy;
        if ($ob === '') return [null, 'ASC'];
        $parts = explode(' ', $ob, 2);
        return [$parts[0], strtoupper($parts[1] ?? 'asc') === 'DESC' ? 'DESC' : 'ASC'];
    }

    // ── Rendering ────────────────────────────────────────────────────────────

    public function render(): void
    {
        if ($this->selection === null) {
            throw new GridException('GridControl: call setDataSource() first.');
        }
        if ($this->columns === []) {
            throw new GridException('GridControl: no columns defined.');
        }

        // ── Resolve active preset & apply its config ─────────────────────────
        $activePresetObj = null;
        $hiddenColumns   = [];
        $presetWidths    = [];

        if ($this->showPresets) {
            // Reload presets from callback for fresh data (signal handlers may
            // have modified the DB since loadState ran).
            if ($this->onPresetsLoad !== null) {
                $this->presets = ($this->onPresetsLoad)();
            }

            // Find active preset for column layout
            $activePresetObj = $this->resolveActivePreset();
            if ($activePresetObj !== null) {
                $this->activePreset = $activePresetObj->id;

                // Reorder columns
                $order = $activePresetObj->getColumnOrder();
                if ($order !== []) {
                    $reordered = [];
                    foreach ($order as $key) {
                        if (isset($this->columns[$key])) {
                            $reordered[$key] = $this->columns[$key];
                        }
                    }
                    // Append any columns not in the preset (newly added)
                    foreach ($this->columns as $key => $col) {
                        if (!isset($reordered[$key])) {
                            $reordered[$key] = $col;
                        }
                    }
                    $this->columns = $reordered;
                }

                $hiddenColumns = $activePresetObj->getHiddenColumns();
                $presetWidths  = $activePresetObj->getColumnWidths();
            } else {
                // No preset resolved — ensure select shows "no preset"
                $this->activePreset = '_none';
            }
        }

        $dataSource = new NetteDbDataSource(clone $this->selection);

        // Inject active theme into ActionColumn instances so they can render
        // primary buttons and dropdown menus with the correct CSS classes.
        foreach ($this->columns as $col) {
            if ($col instanceof ActionColumn) {
                $col->setTheme($this->theme);
            }
        }

        $searchableCols = array_map(
            fn(Column $c) => $c->getDbColumn(),
            array_filter($this->columns, fn(Column $c) => $c->isSearchable())
        );
        if ($this->search !== '' && $searchableCols !== []) {
            $dataSource->applySearch($searchableCols, $this->search);
        }

        if ($this->filters !== [] || $this->multiFilters !== []) {
            $dataSource->applyFilters($this->filters, $this->multiFilters, $this->columns);
        }

        [$sortKey, $sortDir] = $this->parseOrderBy();
        if ($sortKey !== null && isset($this->columns[$sortKey]) && $this->columns[$sortKey]->isSortable()) {
            $dataSource->applySort($this->columns[$sortKey]->getDbColumn(), $sortDir);
        }

        $totalRows  = $dataSource->getTotalCount();
        $totalPages = max(1, (int) ceil($totalRows / $this->itemsPerPage));
        $this->page = min($this->page, $totalPages);

        $paginator = new Paginator();
        $paginator->setItemCount($totalRows);
        $paginator->setItemsPerPage($this->itemsPerPage);
        $paginator->setPage($this->page);

        $rows       = $dataSource->fetchPage($this->page, $this->itemsPerPage);
        $hasFilters = (bool) array_filter($this->columns, fn(Column $c) => $c->isFilterable());

        $tpl = $this->template;
        $tpl->columns        = $this->columns;
        $tpl->rows           = $rows;
        $tpl->totalRows      = $totalRows;
        $tpl->totalPages     = $totalPages;
        $tpl->paginator      = $paginator;
        $tpl->currentPage    = $this->page;
        $tpl->sortKey        = $sortKey;
        $tpl->sortDir        = $sortDir;
        $tpl->orderBy        = $this->orderBy;
        $tpl->search         = $this->search;
        $tpl->filters        = $this->filters;
        $tpl->multiFilters   = $this->multiFilters;
        $tpl->filterLabels   = $this->resolveFilterLabels();
        // Per-column base URLs for DropDownList widgets (JS appends ?q=… itself)
        $ddlSuggestUrls = [];
        foreach ($this->columns as $key => $col) {
            if ($col->getFilter()?->type === FilterType::SelectAjax) {
                $ddlSuggestUrls[$key] = $this->link('suggestDdl!', ['column' => $key]);
            }
        }
        $tpl->ddlSuggestUrls = $ddlSuggestUrls;
        $tpl->aggregates     = $dataSource->fetchAggregates($this->columns);
        $tpl->bulkActions    = $this->bulkActions;
        $tpl->primaryKey     = $this->primaryKey;
        $tpl->hasBulk        = $this->primaryKey !== null && ($this->bulkActions !== [] || $this->showCheckboxes);
        $hasAggregates       = array_filter($this->columns, fn(Column $c) => $c->getFooterAggregate() !== null) !== [];
        $tpl->hasFooter      = $hasAggregates;
        $tpl->renderRowAttrs = \Closure::fromCallable([$this, 'renderRowAttrs']);
        $tpl->theme          = $this->theme;
        $tpl->texts          = $this->resolveTexts();
        $tpl->showToolbar    = $this->showToolbar;
        $tpl->showSearch     = $this->showSearch;
        $tpl->showPagination = $this->showPagination;
        // Build sorted per-page options, ensuring current value is included
        $perPageOptions = $this->perPageOptions;
        if ($perPageOptions !== null && !in_array($this->itemsPerPage, $perPageOptions, true)) {
            $perPageOptions[] = $this->itemsPerPage;
            sort($perPageOptions);
        }
        $tpl->perPageOptions = $perPageOptions;
        $tpl->itemsPerPage   = $this->itemsPerPage;
        $tpl->perPageUrl     = $this->link('perPage!', ['perPage' => '__pp__']);
        $tpl->hasFilters     = $hasFilters;
        $tpl->ajaxEnabled    = $this->ajaxEnabled;
        $tpl->resizable      = $this->resizable;
        $tpl->reorderable    = $this->reorderable;
        $tpl->showColumnToggle = $this->showColumnToggle;
        $tpl->tableLayout    = $this->tableLayout;
        $tpl->uniqueId       = $this->getUniqueId();

        // Presets
        $tpl->presets        = $this->presets;
        $tpl->showPresets    = $this->showPresets;
        $tpl->activePreset   = $this->activePreset;
        $tpl->hiddenColumns  = $hiddenColumns;
        $tpl->presetWidths   = $presetWidths;

        $tpl->setFile(__DIR__ . '/templates/grid.latte');
        $tpl->render();
    }

    /**
     * Find the active preset: first try URL param, then fall back to default.
     */
    private function resolveActivePreset(): ?GridPreset
    {
        if (!$this->showPresets || $this->presets === null || $this->presets === []) {
            return null;
        }

        // User explicitly chose "no preset"
        if ($this->activePreset === '_none') {
            return null;
        }

        // Explicit selection by URL param
        if ($this->activePreset !== '') {
            foreach ($this->presets as $p) {
                if ($p->id === $this->activePreset) {
                    return $p;
                }
            }
        }

        // Fall back to the default preset
        foreach ($this->presets as $p) {
            if ($p->isDefault) {
                return $p;
            }
        }

        return null;
    }

    /**
     * Find a preset by ID, loading from provider if needed.
     */
    private function findPresetById(string $id): ?GridPreset
    {
        // Load presets if not yet resolved
        if ($this->onPresetsLoad !== null) {
            $this->presets = ($this->onPresetsLoad)();
        }
        if ($this->presets === null) {
            return null;
        }
        foreach ($this->presets as $p) {
            if ($p->id === $id) {
                return $p;
            }
        }
        return null;
    }

    /**
     * Apply sort/filters/search/perPage from a preset to the grid state.
     *
     * @param bool $force  If true, always overwrite. If false, only fill in
     *                     values that aren't already set from URL (implicit default).
     */
    private function applyPresetState(GridPreset $preset, bool $force = true): void
    {
        if ($preset->getOrderBy() !== null && ($force || $this->orderBy === '')) {
            $this->orderBy = $preset->getOrderBy();
        }
        if ($preset->getSearch() !== null && ($force || $this->search === '')) {
            $this->search = $preset->getSearch();
        }
        if ($preset->getFilters() !== null && ($force || $this->filters === [])) {
            $this->filters = $preset->getFilters();
        }
        if ($preset->getMultiFilters() !== null && ($force || $this->multiFilters === [])) {
            $this->multiFilters = $preset->getMultiFilters();
        }
        if ($preset->getItemsPerPage() !== null && $force) {
            $this->itemsPerPage = $preset->getItemsPerPage();
        }
    }
}
