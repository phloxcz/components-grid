<?php declare(strict_types=1);

namespace Phlox\Components\Grid\Filter;

use Nette\Database\Table\Selection;

/**
 * Holds the filter configuration for a single column.
 */
final class FilterConfig
{
    private function __construct(
        public readonly FilterType  $type,

        /** Static options for FilterType::Select  [ value => label ] */
        public readonly array       $options     = [],

        /** Dynamic source for FilterType::SelectAjax */
        public readonly ?Selection  $selection   = null,
        public readonly string      $valueColumn = 'id',
        public readonly string      $textColumn  = 'name',

        /** Minimum characters before AJAX suggest fires */
        public readonly int         $minChars    = 2,
    ) {}

    public static function text(): self
    {
        return new self(FilterType::Text);
    }

    /** @param array<string|int, string> $options */
    public static function select(array $options): self
    {
        return new self(FilterType::Select, options: $options);
    }

    public static function selectAjax(
        Selection $selection,
        string    $valueColumn = 'id',
        string    $textColumn  = 'name',
        int       $minChars    = 2,
    ): self {
        return new self(
            FilterType::SelectAjax,
            selection:   $selection,
            valueColumn: $valueColumn,
            textColumn:  $textColumn,
            minChars:    $minChars,
        );
    }

    public static function dateRange(): self
    {
        return new self(FilterType::DateRange);
    }

    public static function numberRange(): self
    {
        return new self(FilterType::NumberRange);
    }

    public static function bool(): self
    {
        return new self(FilterType::Bool);
    }

    /** @param array<string|int, string> $options */
    public static function multiSelect(array $options): self
    {
        return new self(FilterType::MultiSelect, options: $options);
    }
}
