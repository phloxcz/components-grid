<?php declare(strict_types=1);

namespace Phlox\Components\Grid\Column;

/**
 * Numeric column with optional formatting (decimals, thousands separator).
 */
final class NumberColumn extends Column
{
    public function __construct(
        string $key,
        string $label,
        string $dbColumn,
        private int $decimals = 0,
        private string $decimalSeparator = ',',
        private string $thousandsSeparator = ' ',
        private string $prefix = '',
        private string $suffix = '',
    ) {
        parent::__construct($key, $label, $dbColumn);
    }

    public function decimals(int $decimals): static
    {
        $this->decimals = $decimals;
        return $this;
    }

    public function prefix(string $prefix): static
    {
        $this->prefix = $prefix;
        return $this;
    }

    public function suffix(string $suffix): static
    {
        $this->suffix = $suffix;
        return $this;
    }

    public function renderFooter(int|float|string|null $value): string
    {
        if ($value === null) return '';
        $formatted = number_format(
            (float) $value,
            $this->decimals,
            $this->decimalSeparator,
            $this->thousandsSeparator
        );
        return htmlspecialchars($this->prefix . $formatted . $this->suffix);
    }

    public function renderCell(mixed $row): string
    {
        if ($this->renderer !== null) {
            return ($this->renderer)($row);
        }

        $value = $this->resolveValue($row, $this->dbColumn);

        if ($value === null || $value === '') {
            return '–';
        }

        $formatted = number_format(
            (float) $value,
            $this->decimals,
            $this->decimalSeparator,
            $this->thousandsSeparator
        );

        return htmlspecialchars($this->prefix . $formatted . $this->suffix);
    }
}
