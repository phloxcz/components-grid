<?php declare(strict_types=1);

namespace Phlox\Components\Grid\Column;

use Nette\Database\Table\Selection;
use Phlox\Components\Grid\Filter\FilterConfig;
use Phlox\Components\Grid\Filter\FilterType;

/**
 * Abstract base class for all GridControl columns.
 */
abstract class Column
{
    protected bool          $sortable         = false;
    protected bool          $searchable       = false;
    protected bool          $filterable       = false;
    protected bool          $hideable         = true;
    protected ?FilterConfig $filter           = null;
    protected ?string       $filterDbColumn   = null; // override SQL column used in WHERE (default: $dbColumn)
    protected ?string       $footerAggregate  = null; // sum|avg|count|min|max
    protected ?string $width   = null;
    protected ?string $class   = null;
    protected ?string $headerClass = null;
    protected ?\Closure $renderer = null;

    public function __construct(
        protected readonly string $key,
        protected readonly string $label,
        protected readonly string $dbColumn,
    ) {}

    // -------------------------------------------------------------------------
    // Fluent API
    // -------------------------------------------------------------------------

    public function sortable(bool $value = true): static
    {
        $this->sortable = $value;
        return $this;
    }

    public function searchable(bool $value = true): static
    {
        $this->searchable = $value;
        return $this;
    }

    /** Plain text LIKE filter */
    public function filterable(bool $value = true): static
    {
        $this->filterable = $value;
        if ($value) {
            $this->filter = FilterConfig::text();
        }
        return $this;
    }

    /**
     * Select filter – static options.
     * @param array<string|int, string> $options  [ value => label ]
     */
    public function filterableSelect(array $options): static
    {
        $this->filterable = true;
        $this->filter     = FilterConfig::select($options);
        return $this;
    }

    /**
     * Select filter – dynamic AJAX suggest from a DB Selection.
     */
    public function filterableSelectAjax(
        Selection $selection,
        string    $valueColumn = 'id',
        string    $textColumn  = 'name',
        int       $minChars    = 2,
    ): static {
        $this->filterable = true;
        $this->filter     = FilterConfig::selectAjax($selection, $valueColumn, $textColumn, $minChars);
        return $this;
    }

    /** Date range filter – two date inputs (from / to). */
    public function filterableDateRange(): static
    {
        $this->filterable = true;
        $this->filter     = FilterConfig::dateRange();
        return $this;
    }

    /** Number range filter – two number inputs (min / max). */
    public function filterableNumberRange(): static
    {
        $this->filterable = true;
        $this->filter     = FilterConfig::numberRange();
        return $this;
    }

    /** Boolean filter – checkbox (checked = true only, unchecked = no filter). */
    public function filterableBool(): static
    {
        $this->filterable = true;
        $this->filter     = FilterConfig::bool();
        return $this;
    }

    /**
     * Multi-select filter – checkbox dropdown with static options.
     * Submitted as filters[key][] = value1, value2, …
     * Generates: WHERE column IN (?, ?, …)
     *
     * @param array<string|int, string> $options  [ value => label ]
     */
    public function filterableMultiSelect(array $options): static
    {
        $this->filterable = true;
        $this->filter     = FilterConfig::multiSelect($options);
        return $this;
    }

    /**
     * Whether this column can be hidden via the column toggle UI.
     */
    public function hideable(bool $value = true): static
    {
        $this->hideable = $value;
        return $this;
    }

    public function width(string $width): static
    {
        $this->width = $width;
        return $this;
    }

    public function class(string $class): static
    {
        $this->class = $class;
        return $this;
    }

    public function headerClass(string $class): static
    {
        $this->headerClass = $class;
        return $this;
    }

    /**
     * Custom cell renderer callback.
     * Signature: function(ActiveRow|array $row): string
     */
    public function renderer(\Closure $renderer): static
    {
        $this->renderer = $renderer;
        return $this;
    }

    /**
     * Show an aggregate value in the footer for this column.
     * @param string $fn  sum | avg | count | min | max
     */
    public function footerAggregate(string $fn = 'sum'): static
    {
        $this->footerAggregate = strtolower($fn);
        return $this;
    }

    /** Shorthand for footerAggregate('sum') */
    public function footerSum(): static  { return $this->footerAggregate('sum'); }
    /** Shorthand for footerAggregate('avg') */
    public function footerAvg(): static  { return $this->footerAggregate('avg'); }
    /** Shorthand for footerAggregate('count') */
    public function footerCount(): static { return $this->footerAggregate('count'); }
    /** Shorthand for footerAggregate('min') */
    public function footerMin(): static  { return $this->footerAggregate('min'); }
    /** Shorthand for footerAggregate('max') */
    public function footerMax(): static  { return $this->footerAggregate('max'); }

    // -------------------------------------------------------------------------
    // Getters
    // -------------------------------------------------------------------------

    /**
     * Override which SQL column is used in WHERE clauses for this filter.
     * Useful when dbColumn is a related table column (e.g. 'status.name')
     * but filtering should target the FK (e.g. 'statusId' or 'users.statusId').
     *
     *   $grid->addTextColumn('statusId', 'Stav', 'status.name')
     *        ->filterDbColumn('users.statusId')
     *        ->filterableMultiSelect([...]);
     */
    public function filterDbColumn(string $column): static
    {
        $this->filterDbColumn = $column;
        return $this;
    }

    public function getFilterDbColumn(): string
    {
        if ($this->filterDbColumn !== null) {
            return $this->filterDbColumn;
        }
        // If dbColumn references a related table (contains dot), default to $key
        // which is typically the FK column name on the main table.
        if (str_contains($this->dbColumn, '.')) {
            return $this->key;
        }
        return $this->dbColumn;
    }
    public function getKey(): string       { return $this->key; }
    public function getLabel(): string     { return $this->label; }
    public function getDbColumn(): string  { return $this->dbColumn; }
    public function getWidth(): ?string    { return $this->width; }
    public function getClass(): ?string    { return $this->class; }
    public function getHeaderClass(): ?string { return $this->headerClass; }
    public function isSortable(): bool     { return $this->sortable; }
    public function isSearchable(): bool   { return $this->searchable; }
    public function isFilterable(): bool        { return $this->filterable; }
    public function isHideable(): bool          { return $this->hideable; }
    public function getFilter(): ?FilterConfig  { return $this->filter; }
    public function getFilterType(): ?FilterType    { return $this->filter?->type; }
    public function getFooterAggregate(): ?string   { return $this->footerAggregate; }

    // -------------------------------------------------------------------------
    // Rendering
    // -------------------------------------------------------------------------

    /**
     * Render an aggregate value in the footer.
     * Override in subclasses for custom formatting (e.g. NumberColumn adds decimals).
     */
    public function renderFooter(int|float|string|null $value): string
    {
        if ($value === null) return '';
        return htmlspecialchars((string) $value, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    }

    public function renderCell(mixed $row): string
    {
        if ($this->renderer !== null) {
            return ($this->renderer)($row);
        }
        $value = $this->resolveValue($row, $this->dbColumn);
        return htmlspecialchars((string) $value, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    }

    /**
     * Resolve a value from a row, supporting dot-notation for related tables.
     *
     * 'name'        → $row['name']
     * 'status.name' → $row['status']['name']  or  $row->status->name
     *
     * This mirrors how dbColumn works in SQL (table.column), but applied
     * to the ActiveRow / array returned by Nette Database.
     */
    protected function resolveValue(mixed $row, string $column): mixed
    {
        if (!str_contains($column, '.')) {
            return is_array($row) ? ($row[$column] ?? '') : ($row->{$column} ?? '');
        }

        $parts = explode('.', $column, 2);
        [$relation, $field] = $parts;

        if (is_array($row)) {
            $related = $row[$relation] ?? null;
            return is_array($related) ? ($related[$field] ?? '') : '';
        }

        // Nette ActiveRow: $row->relation returns related ActiveRow via ref()
        $related = $row->{$relation} ?? null;
        if ($related === null) {
            return '';
        }
        return is_array($related) ? ($related[$field] ?? '') : ($related->{$field} ?? '');
    }
}
