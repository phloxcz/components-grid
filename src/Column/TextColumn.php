<?php declare(strict_types=1);

namespace Phlox\Components\Grid\Column;

/**
 * Plain text / string column.
 */
final class TextColumn extends Column
{
    private int $maxLength = 0;
    private string $ellipsis = '…';

    /**
     * Truncate cell value to given length.
     */
    public function truncate(int $length, string $ellipsis = '…'): static
    {
        $this->maxLength = $length;
        $this->ellipsis  = $ellipsis;
        return $this;
    }

    public function renderCell(mixed $row): string
    {
        if ($this->renderer !== null) {
            return ($this->renderer)($row);
        }

        $value = (string) $this->resolveValue($row, $this->dbColumn);

        if ($this->maxLength > 0 && mb_strlen($value) > $this->maxLength) {
            $value = mb_substr($value, 0, $this->maxLength) . $this->ellipsis;
        }

        return htmlspecialchars($value, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    }
}
