<?php declare(strict_types=1);

namespace Phlox\Components\Grid\Column;

/**
 * Column that renders a Bootstrap badge based on a value → definition map.
 *
 * Map format:
 *   [
 *     'active'   => ['label' => 'Active',   'color' => 'success'],
 *     'inactive' => ['label' => 'Inactive', 'color' => 'secondary'],
 *   ]
 */
final class BadgeColumn extends Column
{
    private string $defaultColor = 'secondary';

    /**
     * @param array<scalar, array{label: string, color: string}> $map
     */
    public function __construct(
        string $key,
        string $label,
        string $dbColumn,
        private array $map = [],
    ) {
        parent::__construct($key, $label, $dbColumn);
    }

    /** @param array<scalar, array{label: string, color: string}> $map */
    public function map(array $map): static
    {
        $this->map = $map;
        return $this;
    }

    public function defaultColor(string $color): static
    {
        $this->defaultColor = $color;
        return $this;
    }

    public function renderCell(mixed $row): string
    {
        if ($this->renderer !== null) {
            return ($this->renderer)($row);
        }

        $value = $this->resolveValue($row, $this->dbColumn);
        $def   = $this->map[$value] ?? null;

        $label = $def['label'] ?? htmlspecialchars((string) $value);
        $color = $def['color'] ?? $this->defaultColor;

        return sprintf(
            '<span class="badge bg-%s">%s</span>',
            htmlspecialchars($color),
            htmlspecialchars($label)
        );
    }
}
