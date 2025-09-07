<?php

declare(strict_types=1);

namespace PhpTui\Tui\Extension\Core\Widget\Table;

use PhpTui\Tui\Style\Style;
use PhpTui\Tui\Text\Line;
use PhpTui\Tui\Text\Text;

final class TableCell
{
    public function __construct(public Text $content, public Style $style)
    {
    }

    public static function fromString(string $string): self
    {
        return new self(Text::fromLine(Line::fromString($string)), Style::default());
    }

    public static function fromLine(Line $line): self
    {
        return new self(Text::fromLine($line), Style::default());
    }
}
