<?php declare(strict_types=1);

namespace Phlox\Components\Grid;

/**
 * Defines a bulk action available in the grid bulk action bar.
 */
final class BulkAction
{
    /**
     * @param string   $id         Unique identifier, passed to the callback
     * @param string   $caption    Label text
     * @param \Closure $callback   fn(array $ids): void
     * @param string   $icon       CSS icon class (e.g. 'bi bi-trash')
     * @param string   $extraClass Extra CSS classes for the button
     * @param ?string  $confirm    Confirm dialog message (null = no confirm)
     */
    public function __construct(
        public readonly string   $id,
        public readonly string   $caption,
        public readonly \Closure $callback,
        public readonly string   $icon       = '',
        public readonly string   $extraClass = '',
        public readonly ?string  $confirm    = null,
    ) {}
}
