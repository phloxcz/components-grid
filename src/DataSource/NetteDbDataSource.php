<?php declare(strict_types=1);

namespace Phlox\Components\Grid\DataSource;

use Nette\Database\Table\Selection;
use Phlox\Components\Grid\Column\Column;
use Phlox\Components\Grid\Filter\FilterType;

/**
 * Wraps Nette Database Selection and applies sorting, filtering, searching and pagination.
 */
final class NetteDbDataSource
{
    public function __construct(private readonly Selection $selection)
    {}

    /**
     * Apply a global search across all searchable columns.
     * Column names may include a table prefix (e.g. 'users.name').
     */
    public function applySearch(array $columns, string $query): self
    {
        if ($query === '' || $columns === []) {
            return $this;
        }

        $conditions = array_map(fn(string $col) => "$col LIKE ?", $columns);
        $values     = array_fill(0, count($columns), "%$query%");

        $this->selection->where(implode(' OR ', $conditions), ...$values);
        return $this;
    }

    /**
     * Apply per-column filters.
     * Range filters use suffixed keys: col_from / col_to  or  col_min / col_max.
     *
     * Column dbColumn values may include a table prefix (e.g. 'users.name'),
     * which is passed through to SQL as-is — useful when the Selection uses JOINs
     * and column names would otherwise be ambiguous.
     *
     * @param array<string, string>        $filters       flat key=>value from URL state
     * @param array<string, list<string>>  $multiFilters  multi-select key=>[val,…] from URL state
     * @param array<string, Column>        $columns       column instances (for filter type)
     */
    public function applyFilters(array $filters, array $multiFilters, array $columns): self
    {
        foreach ($columns as $key => $col) {
            if (!$col->isFilterable() || $col->getFilter() === null) {
                continue;
            }

            $dbCol = $col->getFilterDbColumn();
            $type  = $col->getFilterType();

            switch ($type) {
                case FilterType::Text:
                    $val = $filters[$key] ?? '';
                    if ($val !== '') {
                        $this->selection->where("$dbCol LIKE ?", "%$val%");
                    }
                    break;

                case FilterType::Select:
                case FilterType::SelectAjax:
                    $val = $filters[$key] ?? '';
                    if ($val !== '') {
                        $this->selection->where("$dbCol = ?", $val);
                    }
                    break;

                case FilterType::Bool:
                    $val = $filters[$key] ?? '';
                    if ($val === '1') {
                        $this->selection->where("$dbCol = ?", 1);
                    } elseif ($val === '0') {
                        $this->selection->where("$dbCol = ?", 0);
                    }
                    break;

                case FilterType::DateRange:
                    $from = $filters[$key . '_from'] ?? '';
                    $to   = $filters[$key . '_to']   ?? '';
                    if ($from !== '') {
                        $this->selection->where("$dbCol >= ?", $from);
                    }
                    if ($to !== '') {
                        $this->selection->where("$dbCol <= ?", $to . ' 23:59:59');
                    }
                    break;

                case FilterType::NumberRange:
                    $min = $filters[$key . '_min'] ?? '';
                    $max = $filters[$key . '_max'] ?? '';
                    if ($min !== '') {
                        $this->selection->where("$dbCol >= ?", $min);
                    }
                    if ($max !== '') {
                        $this->selection->where("$dbCol <= ?", $max);
                    }
                    break;

                case FilterType::MultiSelect:
                    $vals = $multiFilters[$key] ?? [];
                    if ($vals !== []) {
                        $this->selection->where("$dbCol IN ?", $vals);
                    }
                    break;
            }
        }
        return $this;
    }

    public function applySort(string $dbColumn, string $direction): self
    {
        $direction = strtoupper($direction) === 'DESC' ? 'DESC' : 'ASC';
        $this->selection->order("$dbColumn $direction");
        return $this;
    }

    public function getTotalCount(): int
    {
        return (clone $this->selection)->count('*');
    }

    /**
     * @return list<\Nette\Database\Table\ActiveRow>
     */
    public function fetchPage(int $page, int $itemsPerPage): array
    {
        $page   = max(1, $page);
        $offset = ($page - 1) * $itemsPerPage;
        return array_values($this->selection->limit($itemsPerPage, $offset)->fetchAll());
    }

    public function getSelection(): Selection
    {
        return $this->selection;
    }

    /**
     * Compute footer aggregates for columns that request it.
     * Runs on the full filtered dataset (not just the current page).
     *
     * @param  array<string, Column> $columns
     * @return array<string, int|float|string|null>  columnKey => value
     */
    public function fetchAggregates(array $columns): array
    {
        $result = [];
        foreach ($columns as $key => $col) {
            $fn = $col->getFooterAggregate();
            if ($fn === null) {
                continue;
            }
            $dbCol = $col->getDbColumn();
            $expr  = match ($fn) {
                'sum'   => "SUM($dbCol)",
                'avg'   => "AVG($dbCol)",
                'count' => "COUNT($dbCol)",
                'min'   => "MIN($dbCol)",
                'max'   => "MAX($dbCol)",
                default => "SUM($dbCol)",
            };
            $result[$key] = (clone $this->selection)->aggregation($expr);
        }
        return $result;
    }
}
