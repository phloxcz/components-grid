<?php declare(strict_types=1);

namespace Phlox\Components\Grid;

/**
 * Represents a saved grid preset (column layout, filters, sorting, etc.).
 *
 * Config structure:
 *   [
 *       // Column layout
 *       'columnOrder'   => ['id', 'name', 'email', …],
 *       'columnWidths'  => ['id' => '60px', 'name' => '200px'],
 *       'hiddenColumns' => ['guid', 'created_at'],
 *
 *       // Grid state
 *       'orderBy'       => 'name asc',
 *       'search'        => '',
 *       'filters'       => ['status' => 'active', …],
 *       'multiFilters'  => ['role' => ['admin', 'editor'], …],
 *       'itemsPerPage'  => 25,
 *   ]
 */
final class GridPreset
{
    /**
     * @param string $id         Unique identifier (e.g. DB primary key as string)
     * @param string $name       Display name
     * @param bool   $isDefault  Whether this preset is the default one
     * @param array  $config     Full configuration
     */
    public function __construct(
        public readonly string $id,
        public readonly string $name,
        public readonly bool   $isDefault = false,
        public readonly array  $config = [],
    ) {}

    // ── Column layout ────────────────────────────────────────────────────────

    /** @return list<string> */
    public function getColumnOrder(): array
    {
        return $this->config['columnOrder'] ?? [];
    }

    /** @return array<string, string> */
    public function getColumnWidths(): array
    {
        return $this->config['columnWidths'] ?? [];
    }

    /** @return list<string> */
    public function getHiddenColumns(): array
    {
        return $this->config['hiddenColumns'] ?? [];
    }

    // ── Grid state ───────────────────────────────────────────────────────────

    public function getOrderBy(): ?string
    {
        return $this->config['orderBy'] ?? null;
    }

    public function getSearch(): ?string
    {
        return $this->config['search'] ?? null;
    }

    /** @return array<string, string>|null */
    public function getFilters(): ?array
    {
        return $this->config['filters'] ?? null;
    }

    /** @return array<string, list<string>>|null */
    public function getMultiFilters(): ?array
    {
        return $this->config['multiFilters'] ?? null;
    }

    public function getItemsPerPage(): ?int
    {
        $v = $this->config['itemsPerPage'] ?? null;
        return $v !== null ? (int) $v : null;
    }
}
