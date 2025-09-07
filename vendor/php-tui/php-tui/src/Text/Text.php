<?php

declare(strict_types=1);

namespace PhpTui\Tui\Text;

use PhpTui\Tui\Style\Style;
use PhpTui\Tui\Style\Styleable;
use PhpTui\Tui\Style\StyleableTrait;

final class Text implements Styleable
{
    use StyleableTrait;

    /**
     * @param list<Line> $lines
     */
    public function __construct(public array $lines)
    {
    }

    public static function fromString(string $string): self
    {
        return new self(array_map(static function (string $line): Line {
            return Line::fromString($line);
        }, explode("\n", $string)));
    }

    public static function parse(string $string): self
    {
        $placeholder = '{{1162128a-269a-4c09-88be-effc70a62a3a}}';

        $string = preg_replace_callback('/<[^>]+>.*?<\/>/s', static function (array $matches) use ($placeholder): string {
            return str_replace("\n", $placeholder, $matches[0]);
        }, $string);

        // We can only break into new lines at the breaks that are outside of tags.
        $lines = array_map(static function (string $line) use ($placeholder): Line {
            return Line::parse(str_replace($placeholder, "\n", $line));
        }, explode("\n", $string ?? ''));

        return new self($lines);
    }

    public static function fromLine(Line $line): self
    {
        return new self([$line]);
    }

    public static function styled(string $string, Style $style): self
    {
        $text = self::fromString($string);

        return $text->patchStyle($style);
    }

    public function patchStyle(Style $style): self
    {
        foreach ($this->lines as $line) {
            $line->patchStyle($style);
        }

        return $this;
    }

    /**
     * @return int<0,max>
     */
    public function height(): int
    {
        return count($this->lines);
    }

    public static function fromLines(Line ...$lines): self
    {
        return new self(array_values($lines));
    }
}
