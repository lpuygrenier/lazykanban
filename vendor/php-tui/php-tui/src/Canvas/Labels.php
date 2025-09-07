<?php

declare(strict_types=1);

namespace PhpTui\Tui\Canvas;

use ArrayIterator;
use IteratorAggregate;
use PhpTui\Tui\Extension\Core\Widget\Chart\AxisBounds;
use Traversable;

/**
 * @implements IteratorAggregate<Label>
 */
final class Labels implements IteratorAggregate
{
    /**
     * @param array<int,Label> $labels
     */
    public function __construct(private array $labels)
    {
    }

    public static function none(): self
    {
        return new self([]);
    }

    public function add(Label $label): void
    {
        $this->labels[] = $label;
    }

    public function getIterator(): Traversable
    {
        return new ArrayIterator($this->labels);
    }

    public function withinBounds(AxisBounds $xBounds, AxisBounds $yBounds): self
    {
        return new self(array_filter($this->labels, static function (Label $label) use ($xBounds, $yBounds): bool {
            return $xBounds->contains($label->position->x) && $yBounds->contains($label->position->y);
        }));
    }
}
