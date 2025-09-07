<?php

declare(strict_types=1);

namespace PhpTui\Term;

use PhpTui\Term\Event\CharKeyEvent;
use PhpTui\Term\Event\CodedKeyEvent;
use PhpTui\Term\Event\CursorPositionEvent;
use PhpTui\Term\Event\FocusEvent;
use PhpTui\Term\Event\FunctionKeyEvent;
use PhpTui\Term\Event\MouseEvent;

/**
 * Parses input events
 */
final class EventParser
{
    /**
     * @var string[]
     */
    private array $buffer = [];

    /**
     * @var Event[]
     */
    private array $events = [];

    /**
     * @return Event[]
     */
    public function drain(): array
    {
        $events = $this->events;
        $this->events = [];

        return $events;
    }

    public function advance(string $line, bool $more): void
    {
        // split string into bytes
        $bytes = str_split($line);

        foreach ($bytes as $index => $byte) {
            $more = $index + 1 < strlen($line) || $more;

            $this->buffer[] = $byte;

            try {
                $event = $this->parseEvent($this->buffer, $more);
            } catch (ParseError) {
                $this->buffer = [];

                continue;
            }
            if ($event === null) {
                continue;
            }
            $this->events[] = $event;
            $this->buffer = [];
        }
    }

    public static function new(): self
    {
        return new self();
    }

    /**
     * @param string[] $buffer
     */
    private function parseEvent(array $buffer, bool $inputAvailable): ?Event
    {
        if ($buffer === []) {
            return null;
        }

        return match ($buffer[0]) {
            "\x1B" => $this->parseEsc($buffer, $inputAvailable),
            "\x7F" => CodedKeyEvent::new(KeyCode::Backspace),
            "\r" => CodedKeyEvent::new(KeyCode::Enter),
            "\t" => CodedKeyEvent::new(KeyCode::Tab),
            default => $this->parseCtrlOrUtf8Char($buffer),
        };
    }

    /**
     * @param string[] $buffer
     */
    private function parseEsc(array $buffer, bool $inputAvailable): ?Event
    {
        if (count($buffer) === 1) {
            if ($inputAvailable) {
                // _could_ be an escape sequence
                return null;
            }

            return CodedKeyEvent::new(KeyCode::Esc);
        }

        return match ($buffer[1]) {
            '[' => $this->parseCsi($buffer),
            "\x1B" => CodedKeyEvent::new(KeyCode::Esc),
            'O' => (function () use ($buffer): null|FunctionKeyEvent|CodedKeyEvent {
                if (count($buffer) === 2) {
                    return null;
                }

                return match ($buffer[2]) {
                    'P' => FunctionKeyEvent::new(1),
                    'Q' => FunctionKeyEvent::new(2),
                    'R' => FunctionKeyEvent::new(3),
                    'S' => FunctionKeyEvent::new(4),
                    'H' => CodedKeyEvent::new(KeyCode::Home),
                    'F' => CodedKeyEvent::new(KeyCode::End),
                    'D' => CodedKeyEvent::new(KeyCode::Left),
                    'C' => CodedKeyEvent::new(KeyCode::Right),
                    'A' => CodedKeyEvent::new(KeyCode::Up),
                    'B' => CodedKeyEvent::new(KeyCode::Down),
                    default => throw ParseError::couldNotParseOffset($buffer, 22),
                };
            })(),
            default => $this->parseEvent(array_slice($buffer, 1), $inputAvailable),
        };
    }

    /**
     * @param string[] $buffer
     */
    private function parseCsi(array $buffer): ?Event
    {
        if (count($buffer) === 2) {
            return null;
        }

        return match ($buffer[2]) {
            'D' => CodedKeyEvent::new(KeyCode::Left),
            'C' => CodedKeyEvent::new(KeyCode::Right),
            'A' => CodedKeyEvent::new(KeyCode::Up),
            'B' => CodedKeyEvent::new(KeyCode::Down),
            'H' => CodedKeyEvent::new(KeyCode::Home),
            'F' => CodedKeyEvent::new(KeyCode::End),
            'Z' => CodedKeyEvent::new(KeyCode::BackTab, KeyModifiers::SHIFT, KeyEventKind::Press),
            'M' => $this->parseCsiNormalMouse($buffer),
            '<' => $this->parseCsiSgrMouse($buffer),
            'I' => FocusEvent::gained(),
            'O' => FocusEvent::lost(),
            // https://sw.kovidgoyal.net/kitty/keyboard-protocol/#legacy-functional-keys
            'P' => FunctionKeyEvent::new(1),
            'Q' => FunctionKeyEvent::new(2),
            'R' => FunctionKeyEvent::new(3), // this is omitted from crossterm
            'S' => FunctionKeyEvent::new(4),
            ';' => $this->parseCsiModifierKeyCode($buffer),
            '0','1','2','3','4','5','6','7','8','9' => $this->parseCsiMore($buffer),
            default => throw ParseError::couldNotParseOffset($buffer, 2),
        };
    }

    /**
     * @param string[] $buffer
     */
    private function parseCsiMore(array $buffer): ?Event
    {
        // numbered escape code
        if (count($buffer) === 3) {
            return null;
        }

        $lastByte = $buffer[array_key_last($buffer)];
        // the final byte of a CSI sequence can be in the range 64-126
        $ord = ord($lastByte);
        if ($ord < 64 || $ord > 126) {
            return null;
        }

        return match ($lastByte) {
            'M' => $this->parseCsiRxvtMouse($buffer),
            '~' => $this->parseCsiSpecialKeyCode($buffer),
            'R' => $this->parseCsiCursorPosition($buffer),
            default => $this->parseCsiModifierKeyCode($buffer),
        };
    }

    /**
     * @param string[] $buffer
     */
    private function parseCsiSpecialKeyCode(array $buffer): Event
    {
        $str = implode('', array_slice($buffer, 2, (int)array_key_last($buffer)));

        $split = array_map(
            fn (string $substr): int => $this->filterToInt($substr) ?? 0,
            explode(';', $str),
        );
        $first = $split[array_key_first($split)];

        $keycode = match ($first) {
            1,7 => KeyCode::Home,
            2 => KeyCode::Insert,
            4,8 => KeyCode::End,
            5 => KeyCode::PageUp,
            6 => KeyCode::PageDown,
            3 => KeyCode::Delete,
            default => null,
        };
        if (null !== $keycode) {
            return CodedKeyEvent::new($keycode);
        }

        return match($first) {
            11,12,13,14,15 => FunctionKeyEvent::new($first - 10),
            17,18,19,20,21 => FunctionKeyEvent::new($first - 11),
            23,24,25,26 => FunctionKeyEvent::new($first - 12),
            28,29 => FunctionKeyEvent::new($first - 15),
            31,32,33,34 => FunctionKeyEvent::new($first - 17),
            default => throw new ParseError(
                sprintf(
                    'Could not parse char "%s" in CSI event: %s',
                    $first,
                    json_encode(implode('', $buffer))
                )
            ),
        };
    }

    /**
     * @param non-empty-array<string> $buffer
     */
    private function parseCtrlOrUtf8Char(array $buffer): ?Event
    {
        $char = $buffer[0];
        $code = ord($char);

        // control key for alpha chars
        if ($code >= 1 && $code <= 26) {
            return CharKeyEvent::new(chr($code - 1 + ord('a')), KeyModifiers::CONTROL);
        }

        // control 4-7 !?
        if ($code >= 28 && $code <= 31) {
            return CharKeyEvent::new(chr(
                $code - ord("\x1C") + ord('4')
            ), KeyModifiers::CONTROL);
        }

        // control space
        if ($char === "\x0") {
            return CharKeyEvent::new(' ', KeyModifiers::CONTROL);
        }

        $char = implode('', $buffer);
        if (false === mb_check_encoding($char, 'utf-8')) {
            // this function either throws an exception or
            // returns NULL indicating we need to parse more bytes
            $this->parseUtf8($buffer);

            return null;
        }

        return $this->charToEvent($char);
    }

    private function charToEvent(string $char): Event
    {
        $modifiers = 0;
        $ord = ord($char);
        if ($ord >= 65 && $ord <= 90) {
            $modifiers = KeyModifiers::SHIFT;
        }

        return CharKeyEvent::new($char, $modifiers);
    }

    /**
     * @param string[] $buffer
     */
    private function parseCsiModifierKeyCode(array $buffer): Event
    {
        $str = implode('', array_slice($buffer, 2, (int)array_key_last($buffer)));
        // split string into bytes
        $parts = explode(';', $str);

        [$modifiers, $kind] = (function () use ($parts): array {
            $modifierAndKindCode = $this->modifierAndKindParsed($parts);
            if (null !== $modifierAndKindCode) {
                return [
                    $this->parseModifiers($modifierAndKindCode[0]),
                    $this->parseKeyEventKind($modifierAndKindCode[1]),
                ];
            }

            // TODO: if buffer.len > 3

            return [KeyModifiers::NONE, KeyEventKind::Press];
        })();

        $key = $buffer[array_key_last($buffer)];
        $codedKey = match ($key) {
            'A' => KeyCode::Up,
            'B' => KeyCode::Down,
            'C' => KeyCode::Right,
            'D' => KeyCode::Left,
            'F' => KeyCode::End,
            'H' => KeyCode::Home,
            default => null,
        };
        if (null !== $codedKey) {
            return CodedKeyEvent::new($codedKey, $modifiers, $kind);
        }
        $fNumber = match ($key) {
            'P' => 1,
            'Q' => 2,
            'R' => 3,
            'S' => 4,
            default => null,
        };
        if (null !== $fNumber) {
            return FunctionKeyEvent::new($fNumber, $modifiers, $kind);
        }

        throw new ParseError('Could not parse event');
    }

    /**
     * @param string[] $parts
     * @return ?array{int,int}
     */
    private function modifierAndKindParsed(array $parts): ?array
    {
        if (!isset($parts[1])) {
            throw new ParseError('Could not parse modifier');
        }
        $parts = explode(':', $parts[1]);
        $modifierMask = $this->filterToInt($parts[0]);
        if (null === $modifierMask) {
            return null;
        }
        if (isset($parts[1])) {
            $kindCode = $this->filterToInt($parts[1]);
            if (null === $kindCode) {
                return null;
            }

            return [$modifierMask, $kindCode];
        }

        return [$modifierMask, 1];
    }

    private function filterToInt(string $substr): ?int
    {
        $str = array_reduce(
            str_split($substr),
            function (string $ac, string $char): string {
                if (false === is_numeric($char)) {
                    return $ac;
                }

                return $ac . $char;
            },
            ''
        );
        if ($str === '') {
            return null;
        }

        return (int) $str;
    }

    /**
     * @return int-mask-of<KeyModifiers::*>
     */
    private function parseModifiers(int $mask): int
    {
        $modifierMask = max(0, $mask - 1);
        $modifiers = KeyModifiers::NONE;
        if (($modifierMask & 1) !== 0) {
            $modifiers |= KeyModifiers::SHIFT;
        }
        if (($modifierMask & 2) !== 0) {
            $modifiers |= KeyModifiers::ALT;
        }
        if (($modifierMask & 4) !== 0) {
            $modifiers |= KeyModifiers::CONTROL;
        }
        if (($modifierMask & 8) !== 0) {
            $modifiers |= KeyModifiers::SUPER;
        }
        if (($modifierMask & 16) !== 0) {
            $modifiers |= KeyModifiers::HYPER;
        }
        if (($modifierMask & 32) !== 0) {
            $modifiers |= KeyModifiers::META;
        }

        return $modifiers;
    }

    private function parseKeyEventKind(int $kind): KeyEventKind
    {
        return match ($kind) {
            1 => KeyEventKind::Press,
            2 => KeyEventKind::Repeat,
            3 => KeyEventKind::Release,
            default => KeyEventKind::Press,
        };
    }

    /**
     * @param string[] $buffer
     */
    private function parseCsiNormalMouse(array $buffer): ?Event
    {
        if (count($buffer) < 6) {
            return null;
        }

        $cb = ord($buffer[3]) - 32;
        if ($cb < 0) {
            throw ParseError::couldNotParseOffset($buffer, 3, 'invalid button click value');
        }
        [$kind, $modifiers, $button] = $this->parseCb($cb);

        // See http://www.xfree86.org/current/ctlseqs.html#Mouse%20Tracking
        // The upper left character position on the terminal is denoted as 1,1.
        // Subtract 1 to keep it synced with cursor
        $cx = max(0, (ord($buffer[4]) - 32) - 1);
        $cy = max(0, (ord($buffer[5]) - 32) - 1);

        return MouseEvent::new($kind, $button, $cx, $cy, $modifiers);
    }

    /**
     * Cb is the byte of a mouse input that contains the button being used, the key modifiers being
     * held and whether the mouse is dragging or not.
     *
     * Bit layout of cb, from low to high:
     *
     * - button number
     * - button number
     * - shift
     * - meta (alt)
     * - control
     * - mouse is dragging
     * - button number
     * - button number
     *
     * @return array{MouseEventKind,int-mask-of<KeyModifiers::*>,MouseButton}
     */
    private function parseCb(int $cb): array
    {
        $buttonNumber = ($cb & 0b0000_0011) | (($cb & 0b1100_0000) >> 4);
        $dragging = ($cb & 0b0010_0000) === 0b0010_0000;

        [$kind, $button] = match ([$buttonNumber, $dragging]) {
            [0, false] => [MouseEventKind::Down, MouseButton::Left],
            [1, false] => [MouseEventKind::Down, MouseButton::Middle],
            [2, false] => [MouseEventKind::Down, MouseButton::Right],
            [0, true] => [MouseEventKind::Drag, MouseButton::Left],
            [1, true] => [MouseEventKind::Drag, MouseButton::Middle],
            [2, true] => [MouseEventKind::Drag, MouseButton::Right],
            [3, true], [4, true], [5, true] => [MouseEventKind::Moved, MouseButton::None],
            [4, false] => [MouseEventKind::ScrollUp, MouseButton::None],
            [5, false] => [MouseEventKind::ScrollDown, MouseButton::None],
            [6, false] => [MouseEventKind::ScrollLeft, MouseButton::None],
            [7, false] => [MouseEventKind::ScrollRight, MouseButton::None],
            default => throw new ParseError(sprintf(
                'Could not parse mouse event: button number: %d, dragging: %s',
                $buttonNumber,
                $dragging ? 'true' : 'false'
            ))
        };
        $modifiers = KeyModifiers::NONE;

        if (($cb & 0b0000_0100) === 0b0000_0100) {
            $modifiers |= KeyModifiers::SHIFT;
        }
        if (($cb & 0b0000_1000) === 0b0000_1000) {
            $modifiers |= KeyModifiers::ALT;
        }
        if (($cb & 0b0001_0000) === 0b0001_0000) {
            $modifiers |= KeyModifiers::CONTROL;
        }

        return [$kind, $modifiers, $button];
    }

    /**
     * @param string[] $buffer
     */
    private function parseCsiRxvtMouse(array $buffer): Event
    {
        $s = implode('', array_slice($buffer, 2, -1));
        $split = explode(';', $s);
        if (!array_key_exists(2, $split)) {
            throw new ParseError(sprintf(
                'Could not parse RXVT mouse seq: %s',
                $s
            ));
        }
        [$kind, $modifiers, $button] = $this->parseCb((int) ($split[0]) - 32);
        $cx = (int) ($split[1]) - 1;
        $cy = (int) ($split[2]) - 1;

        return MouseEvent::new(
            $kind,
            $button,
            max(0, $cx),
            max(0, $cy),
            $modifiers
        );
    }

    /**
     * @param string[] $buffer
     */
    private function parseCsiSgrMouse(array $buffer): ?Event
    {
        $lastChar = $buffer[array_key_last($buffer)];
        if (!in_array($lastChar, ['m', 'M'], true)) {
            return null;
        }
        $s = implode('', array_slice($buffer, 3, -1));
        $split = explode(';', $s);
        [$kind, $modifiers, $button] = $this->parseCb((int) ($split[0]));
        $cx = (int) ($split[1]) - 1;
        $cy = (int) ($split[2]) - 1;

        if ($lastChar === 'm') {
            $kind = match ($kind) {
                MouseEventKind::Down => MouseEventKind::Up,
                default => $kind,
            };
        }

        return MouseEvent::new(
            $kind,
            $button,
            max(0, $cx),
            max(0, $cy),
            $modifiers
        );
    }

    /**
     * @param string[] $buffer
     */
    private function parseCsiCursorPosition(array $buffer): ?Event
    {
        $s = implode('', array_slice($buffer, 2, -1));
        $split = explode(';', $s);
        if (count($split) !== 2) {
            return null;
        }

        return new CursorPositionEvent(
            max(0, (int) ($split[1]) - 1),
            max(0, (int) ($split[0]) - 1),
        );
    }

    /**
     * @param non-empty-array<string> $buffer
     */
    private function parseUtf8(array $buffer): void
    {
        $firstByte = $buffer[0];
        $ord = ord($firstByte);

        $requiredBytes = match(true) {
            ($ord <= 0x7F) => 1,
            ($ord >= 0xC0 && $ord <= 0xDF) => 2,
            ($ord >= 0xE0 && $ord <= 0xEF) => 3,
            ($ord >= 0xF0 && $ord <= 0xF7) => 4,
            default => throw new ParseError('Could not parse'),
        };

        // NOTE: not sure why this is here...
        // https://github.com/crossterm-rs/crossterm/blob/08762b3ef4519e7f834453bf91e3fe36f4c63fe7/src/event/sys/unix/parse.rs#L845-L846
        //
        // More than 1 byte, check them for 10xxxxxx pattern
        if ($requiredBytes > 1 && count($buffer) > 1) {
            foreach (array_slice($buffer, 1) as $byte) {
                if ((ord($byte) & ~0b0011_1111) != 0b1000_0000) {
                    throw new ParseError('Could not parse event');
                }
            }
        }

        if (count($buffer) < $requiredBytes) {
            // all bytes look good so far, but we need more
            return;
        }

        throw new ParseError('Could not parse UTF-8');
    }
}
