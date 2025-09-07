<?php

declare(strict_types=1);

namespace PhpTui\Tui\Layout;

use PhpTui\Tui\Layout\Constraint\LengthConstraint;
use PhpTui\Tui\Layout\Constraint\MaxConstraint;
use PhpTui\Tui\Layout\Constraint\MinConstraint;
use PhpTui\Tui\Layout\Constraint\PercentageConstraint;
use Stringable;

/**
 * Implemented this "interface" as an abstract class
 * to allow easy access to factory methods
 */
abstract class Constraint implements Stringable
{
    public static function length(int $length): LengthConstraint
    {
        return new LengthConstraint($length);
    }

    public static function percentage(int $percentage): PercentageConstraint
    {
        return new PercentageConstraint($percentage);
    }

    public static function max(int $max): MaxConstraint
    {
        return new MaxConstraint($max);
    }

    public static function min(int $min): MinConstraint
    {
        return new MinConstraint($min);
    }
}
