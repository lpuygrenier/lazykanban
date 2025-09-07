<?php

declare(strict_types=1);

namespace PhpTui\Tui\Widget;

use PhpTui\Tui\Symbol\LineSet;

enum BorderType
{
    case Plain;
    case Rounded;
    case Double;
    case Thick ;

    public function lineSet(): LineSet
    {
        return match ($this) {
            self::Plain => LineSet::plain(),
            self::Rounded => LineSet::rounded(),
            self::Double => LineSet::double(),
            self::Thick => LineSet::thick(),
        };
    }
}
