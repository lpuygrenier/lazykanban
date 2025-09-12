<?php

declare(strict_types=1);

namespace Lpuygrenier\Lazykanban\Gui\Component\TextArea;

use OutOfRangeException;
use PhpTui\Tui\Position\Position;
use RuntimeException;

/**
 * @experimental 
 * Code is from PR: https://github.com/php-tui/php-tui/pull/170 @dantleech
 */
final class TextEditor {
    /**
     * @param list<string> $lines
     */
    private function __construct(
        private Position $cursor,
        private array  $lines,
    ) {
    }

    public static function fromString(string $contents): self
    {
        return new self(Position::at(0, 0), explode("\n", $contents));
    }

    public function toString(): string
    {
        return implode("\n", $this->lines);
    }

    /**
     * Return the text editors cursor position normalized to the bounds of the
     * current line.
     *
     * If the cursor position is [10,0] and the current line is:
     *
     * ```
     * Hello
     * ```
     *
     * Then the cursor position will be returned as [4,0].
     */
    public function cursorPosition(): Position
    {
        return $this->cursor->change(function (int $x, int $y) {
            $line = $this->lines[$y] ?? null;
            if ($line === null) {
                $y = count($this->lines) - 1;
                $line = $this->lines[$y];
            }
            if ($x > mb_strlen($line)) {
                $x = max(0, mb_strlen($line) - 1);
            }

            return [
                $x,
                $y
            ];
        });
    }

    /**
     * Insert text at the given offset. If length is provided it will replace
     * that number of multibyte characters.
     *
     * @param int<0,max> $length
     */
    public function insert(string $text, int $length = 0): void
    {
        /** @phpstan-ignore-next-line */
        if ($length < 0) {
            throw new OutOfRangeException(sprintf(
                'Insert length must be > 0, got %d',
                $length
            ));
        }

        $line = $this->resolveLine();
        $line = sprintf(
            '%s%s%s',
            mb_substr($line, 0, $this->cursor->x),
            $text,
            mb_substr($line, $this->cursor->x + $length, mb_strlen($line) - $this->cursor->x),
        );
        $this->cursor->x += mb_strlen($text);
        $this->setLine($line);
    }

    public function lineStart(): void
    {
        $this->cursor->x = 0;
        $line = $this->resolveLine();
        if (mb_substr($line, 0, 1) === ' ') {
            $this->seekWordNext();
        }
    }

    public function lineEnd(): void
    {
        $line = $this->resolveLine();
        $this->cursor->x = mb_strlen($line) - 1;
    }

    public function delete(): void
    {
        $line = $this->resolveLine();
        $line = sprintf(
            '%s%s',
            mb_substr($line, 0, $this->cursor->x),
            mb_substr($line, $this->cursor->x + 1),
        );
        $this->setLine($line);
        $lineEnd = mb_strlen($line) - 1;
        if ($lineEnd < $this->cursor->x) {
            $this->cursor->x = max(0, $lineEnd);
        }
    }

    public function deleteBackwards(int $length = 1): void
    {
        $line = $this->resolveLine();
        $line = sprintf(
            '%s%s',
            mb_substr($line, 0, max(0, $this->cursor->x - $length)),
            mb_substr($line, $this->cursor->x, max(0, mb_strlen($line) - $this->cursor->x)),
        );
        $this->setLine($line);
        $this->cursor->x = max(0, $this->cursor->x - 1);
    }

    public function cursorLeft(int $amount = 1): void
    {
        $this->cursor = $this->cursor->change(
            static fn (int $x, int $y): array => [max(0, $x - $amount), $y]
        );
    }

    public function cursorRight(int $amount = 1): void
    {
        $line = $this->resolveLine();
        $this->cursor = $this->cursor->change(
            static fn (int $x, int $y): array => [
                min(mb_strlen($line), $x + $amount),
                $y
            ]
        );
    }

    public function cursorDown(int $amount = 1): void
    {
        $this->cursor = $this->cursor->change(
            fn (int $x, int $y): array => [$x, min(count($this->lines) - 1, $y + $amount)]
        );
    }
    public function cursorUp(int $amount = 1): void
    {
        $this->cursor = $this->cursor->change(
            static fn (int $x, int $y): array => [
                $x,
                max(0, $y - $amount)
            ]
        );
    }

    /**
     * @param string[] $lines
     */
    public static function fromLines(array $lines): self
    {
        return new self(Position::at(0, 0), $lines);
    }

    public function newLine(): void
    {
        $line = $this->resolveLine();
        $pre = mb_substr($line, 0, $this->cursor->x);
        $this->lines = array_values([
            ...array_slice($this->lines, 0, $this->cursor->y),
            $pre,
            ...array_slice($this->lines, $this->cursor->y)
        ]);
        $post = mb_substr($line, $this->cursor->x);
        $this->cursorDown();
        $this->setLine($post);
        $this->cursor->x = 0;
    }

    public function moveCursor(Position $position): void
    {
        $this->cursor = $position->change(function (int $x, int $y): array {
            $line = $this->lines[$y] ?? null;
            if ($line === null) {
                $y = count($this->lines) - 1;
                $line = $this->lines[$y];
            }
            if ($x > mb_strlen($line)) {
                $x = mb_strlen($line) - 1;
            }

            return [
                $x,
                $y
            ];
        });
    }

    public function lineCount(): int
    {
        return count($this->lines);
    }

    public function seekWordPrev(): void
    {
        if ($this->cursor->x === 0 && $this->cursor->y === 0) {
            return;
        }

        if ($this->cursor->x === 0) {
            $this->cursorUp();
            $this->cursorEndOfLine();
            $this->seekWordPrev();

            return;
        }

        $line = mb_substr($this->resolveLine(), 0, $this->cursor->x);
        $split = preg_split('{(\s+)}', $line, -1, PREG_SPLIT_OFFSET_CAPTURE);

        if (false === $split) {
            return;
        }

        if ($split !== []) {
            $last = array_pop($split);
            if ($this->cursor->x === $last[1]) {
                $last = array_pop($split);
            }
            if ($last === null) {
                return;
            }
            $this->cursor->x = $last[1];

            return;
        }
    }

    public function seekWordNext(): void
    {
        $line = mb_substr($this->resolveLine(), $this->cursor->x);
        $split = preg_split('{(\s+)}', $line, -1, PREG_SPLIT_OFFSET_CAPTURE);

        if (false === $split) {
            return;
        }

        if (count($split) > 1) {
            // remove first element
            array_shift($split);
            $last = array_shift($split);
            if (null === $last) {
                // should never happen
                return;
            }
            $this->cursor->x = $last[1] + $this->cursor->x;

            return;
        }

        if (!isset($this->lines[$this->cursor->y + 1])) {
            return;
        }
        $this->cursor->y++;
        $this->cursor->x = 0;
        if (!str_starts_with($this->lines[$this->cursor->y], ' ')) {
            return;
        }
        $this->seekWordNext();
    }

    /**
     * @return string[]
     */
    public function viewportLines(int $offset, int $height): array
    {
        return array_slice($this->lines, $offset, $height);
    }

    private function resolveLine(): string
    {
        $position = $this->cursor;

        // if the line at the cursor doesn't exist
        if (!isset($this->lines[$position->y])) {
            throw new RuntimeException(sprintf(
                'There is no line at position: %d',
                $position->y
            ));
        }

        // return the current line
        return $this->lines[$position->y];
    }

    private function setLine(string $line): void
    {
        $position = $this->cursor;
        $this->lines[$position->y] = $line;
    }

    private function cursorEndOfLine(): void
    {
        $line = $this->resolveLine();
        $this->cursor->x = mb_strlen($line);
    }
}