<?php declare(strict_types=1);

namespace Phlox\Components\Grid\Column;

/**
 * Boolean column – renders configurable true/false labels.
 */
final class BoolColumn extends Column
{
    public function __construct(
        string $key,
        string $label,
        string $dbColumn,
        private string $trueLabel  = '✔',
        private string $trueClass  = 'text-success',
        private string $falseLabel = '✘',
        private string $falseClass = 'text-danger',
    ) {
        parent::__construct($key, $label, $dbColumn);
    }

    public function trueLabel(string $label, string $class = 'text-success'): static
    {
        $this->trueLabel = $label;
        $this->trueClass = $class;
        return $this;
    }

    public function falseLabel(string $label, string $class = 'text-danger'): static
    {
        $this->falseLabel = $label;
        $this->falseClass = $class;
        return $this;
    }

    public function renderCell(mixed $row): string
    {
        if ($this->renderer !== null) {
            return ($this->renderer)($row);
        }

        $value = $this->resolveValue($row, $this->dbColumn);

        if ((bool) $value) {
            return sprintf('<span class="%s">%s</span>', htmlspecialchars($this->trueClass), htmlspecialchars($this->trueLabel));
        }

        return sprintf('<span class="%s">%s</span>', htmlspecialchars($this->falseClass), htmlspecialchars($this->falseLabel));
    }
}
