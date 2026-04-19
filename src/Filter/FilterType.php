<?php declare(strict_types=1);

namespace Phlox\Components\Grid\Filter;

enum FilterType: string
{
    case Text        = 'text';         // LIKE %value%
    case Select      = 'select';       // = value  (static options array)
    case SelectAjax  = 'select_ajax';  // = value  (dynamic via AJAX suggest)
    case DateRange   = 'date_range';   // BETWEEN date_from AND date_to
    case NumberRange = 'number_range'; // BETWEEN num_min AND num_max
    case Bool        = 'bool';         // = 1 / = 0
    case MultiSelect = 'multi_select'; // IN (v1, v2, …)  (static options, checkbox dropdown)
}
