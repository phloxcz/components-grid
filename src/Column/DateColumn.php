<?php declare(strict_types=1);

namespace Phlox\Components\Grid\Column;

/**
 * Date / datetime column with configurable format.
 */
final class DateColumn extends Column
{
    public function __construct(
        string $key,
        string $label,
        string $dbColumn,
        private string $format = 'd.m.Y',
        private string $emptyValue = '–',
    ) {
        parent::__construct($key, $label, $dbColumn);
    }

    public function format(string $format): static
    {
        $this->format = $format;
        return $this;
    }

    public function emptyValue(string $value): static
    {
        $this->emptyValue = $value;
        return $this;
    }

    public function renderCell(mixed $row): string
    {
        if ($this->renderer !== null) {
            return ($this->renderer)($row);
        }

        $value = $this->resolveValue($row, $this->dbColumn);

        if ($value === null || $value === '') {
            return htmlspecialchars($this->emptyValue);
        }

        if ($value instanceof \DateTimeInterface) {
            return $value->format($this->format);
        }

        try {
            return (new \DateTime((string) $value))->format($this->format);
        } catch (\Exception) {
            return htmlspecialchars((string) $value);
        }
    }

    public function renderFooter(int|float|string|null $value): string
    {
        if ($value === null || $value === '') {
            return '';
        }

        if ($value instanceof \DateTimeInterface) {
            return $value->format($this->format);
        }

        try {
            return (new \DateTime((string) $value))->format($this->format);
        } catch (\Exception) {
            return htmlspecialchars((string) $value);
        }
    }
}
