<?php

declare(strict_types=1);

namespace PhpTui\Term;

use PhpTui\Term\Action\AlternateScreenEnable;
use PhpTui\Term\Action\Clear;
use PhpTui\Term\Action\EnableCursorBlinking;
use PhpTui\Term\Action\EnableLineWrap;
use PhpTui\Term\Action\MoveCursorDown;
use PhpTui\Term\Action\MoveCursorLeft;
use PhpTui\Term\Action\MoveCursorNextLine;
use PhpTui\Term\Action\MoveCursorPrevLine;
use PhpTui\Term\Action\MoveCursorRight;
use PhpTui\Term\Action\MoveCursorToColumn;
use PhpTui\Term\Action\MoveCursorToRow;
use PhpTui\Term\Action\MoveCursorUp;
use PhpTui\Term\Action\PrintString;
use PhpTui\Term\Action\RestoreCursorPosition;
use PhpTui\Term\Action\SaveCursorPosition;
use PhpTui\Term\Action\ScrollDown;
use PhpTui\Term\Action\ScrollUp;
use PhpTui\Term\Action\SetCursorStyle;
use PhpTui\Term\Action\SetTerminalTitle;

/**
 * Parse ANSI escape sequences (back) to painter actions.
 *
 * Note this is primarily only intended to support ANSI escape sequences
 * emitted by this library (i.e. the Painter actions).
 */
final class AnsiParser
{
    /**
     * @var string[]
     */
    private array $buffer = [];

    /**
     * @var Action[]
     */
    private array $actions = [];

    public function __construct(private readonly bool $throw = false)
    {
    }

    /**
     * @return Action[]
     */
    public function drain(): array
    {
        $actions = $this->actions;
        $this->actions = [];
        $strings = [];

        // compress strings
        $newActions = [];
        foreach ($actions as $action) {
            if ($action instanceof PrintString) {
                $strings[] = $action;

                continue;
            }
            if ($strings) {
                $newActions[] = Actions::printString(
                    implode('', array_map(static fn (PrintString $s): string => $s->string, $strings))
                );
                $strings = [];
            }
            $newActions[] = $action;
        }
        if ($strings) {
            $newActions[] = Actions::printString(
                implode('', array_map(static fn (PrintString $s): string => $s->string, $strings))
            );
        }

        return $newActions;
    }

    public function advance(string $line, bool $more): void
    {
        // split string into bytes
        $chars = mb_str_split($line);

        foreach ($chars as $index => $char) {
            $more = $index + 1 < strlen($line) || $more;

            $this->buffer[] = $char;

            try {
                $action = $this->parseAction($this->buffer, $more);
            } catch (ParseError $error) {
                if ($this->throw) {
                    throw $error;
                }
                $this->buffer = [];

                continue;
            }
            if ($action === null) {
                continue;
            }
            $this->actions[] = $action;
            $this->buffer = [];
        }
    }

    /**
     * @return Action[]
     */
    public static function parseString(string $output, bool $throw = false): array
    {
        $parser = new self($throw);
        $parser->advance($output, true);

        return $parser->drain();
    }

    /**
     * @param string[] $buffer
     */
    private function parseAction(array $buffer, bool $more): ?Action
    {
        return match ($buffer[0]) {
            "\x1B" => $this->parseEsc($buffer, $more),
            default => Actions::printString($buffer[0])
        };
    }

    /**
     * @param string[] $buffer
     */
    private function parseEsc(array $buffer, bool $more): ?Action
    {
        if (count($buffer) === 1) {
            return null;
        }

        return match ($buffer[1]) {
            '[' => $this->parseCsi($buffer, $more),
            ']' => $this->parseOsc($buffer, $more),
            '7' => new SaveCursorPosition(),
            '8' => new RestoreCursorPosition(),
            default => Actions::printString($buffer[0])
        };
    }

    /**
     * @param string[] $buffer
     */
    private function parseCsi(array $buffer, bool $more): ?Action
    {
        if (count($buffer) === 2) {
            return null;
        }

        return match ($buffer[2]) {
            '0','1','2','3','4','5','6','7','8','9' => $this->parseCsiSeq($buffer),
            '?' => $this->parsePrivateModes($buffer),
            'J' => Actions::clear(ClearType::FromCursorDown),
            'K' => Actions::clear(ClearType::UntilNewLine),
            'S' => Actions::scrollUp(),
            'T' => Actions::scrollDown(),
            default => throw ParseError::couldNotParseOffset($buffer, 2, 'Could not parse CSI sequence'),
        };

    }

    /**
     * @param string[] $buffer
     */
    private function parseCsiSeq(array $buffer): ?Action
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
            'm' => $this->parseGraphicsMode($buffer),
            'H' => $this->parseCursorPosition($buffer),
            'J', 'K' => $this->parseClear($buffer),
            'n' => Actions::requestCursorPosition(),
            'E', 'F', 'G', 'd', 'A', 'C', 'B', 'D' => $this->parseCursorMovement($buffer),
            'q' => $this->parseCursorStyle($buffer),
            'S', 'T' => $this->parseScroll($buffer),
            default => throw ParseError::couldNotParseOffset(
                $buffer,
                intval(array_key_last($buffer)),
                'Do not know how to parse CSI sequence'
            ),
        };
    }

    /**
     * @param string[] $buffer
     */
    private function parseGraphicsMode(array $buffer): Action
    {
        $string = implode('', array_slice($buffer, 2, -1));
        $parts = explode(';', $string);

        // true colors
        if (count($parts) === 5) {
            $rgb = array_map(static fn (string $index): int => (int) $index, array_slice($parts, -3));
            $rgb = array_map(static fn (int $byte): int => min(255, max(0, $byte)), $rgb);

            return match ($parts[0]) {
                '48' => Actions::setRgbBackgroundColor(...$rgb),
                '38' => Actions::setRgbForegroundColor(...$rgb),
                default => throw new ParseError(sprintf('Could not parse graphics mode: %s', json_encode(implode('', $buffer)))),
            };
        }
        if (count($parts) === 3) {
            return match ($parts[0]) {
                '48' => Actions::setRgbBackgroundColor(...Colors256::indexToRgb((int) ($parts[2]))),
                '38' => Actions::setRgbForegroundColor(...Colors256::indexToRgb((int) ($parts[2]))),
                default => throw new ParseError(sprintf('Could not parse graphics mode: %s', json_encode(implode('', $buffer)))),
            };
        }

        $code = (int) ($parts[0]);

        return match ($parts[0]) {
            '39' => Actions::setForegroundColor(Colors::Reset),
            '49' => Actions::setBackgroundColor(Colors::Reset),
            '1' => Actions::bold(true),
            '22' => Actions::bold(false),
            '2' => Actions::dim(true),
            '22' => Actions::dim(false),
            '3' => Actions::italic(true),
            '23' => Actions::italic(false),
            '4' => Actions::underline(true),
            '24' => Actions::underline(false),
            '5' => Actions::slowBlink(true),
            '25' => Actions::slowBlink(false),
            '7' => Actions::reverse(true),
            '27' => Actions::reverse(false),
            '8' => Actions::hidden(true),
            '28' => Actions::hidden(false),
            '9' => Actions::strike(true),
            '29' => Actions::strike(false),
            '0' => Actions::reset(),
            default => match (true) {
                str_starts_with($parts[0], '3') => Actions::setForegroundColor($this->inverseColorIndex($code, false)),
                str_starts_with($parts[0], '4') => Actions::setBackgroundColor($this->inverseColorIndex($code, true)),
                str_starts_with($parts[0], '9') => Actions::setForegroundColor($this->inverseColorIndex($code, false)),
                str_starts_with($parts[0], '10') => Actions::setBackgroundColor($this->inverseColorIndex($code, true)),
                default => throw new ParseError(sprintf('Could not parse graphics mode: %s', json_encode(implode('', $buffer)))),
            },
        };
    }

    /**
     * @param string[] $buffer
     */
    private function parsePrivateModes(array $buffer): ?Action
    {
        $last = $buffer[array_key_last($buffer)];
        if (count($buffer) === 3) {
            return null;
        }

        return match ($buffer[3]) {
            '2' => $this->parsePrivateModes2($buffer),
            '1' => $this->parsePrivateModes2($buffer),
            '7' => $this->parseLineWrap($buffer),
            default => throw ParseError::couldNotParseOffset($buffer, 3, 'Could not parse private mode'),
        };
    }

    /**
     * @param string[] $buffer
     */
    private function parsePrivateModes2(array $buffer): ?Action
    {
        if (count($buffer) === 4) {
            return null;
        }
        if (count($buffer) === 5) {
            return null;
        }

        return match ($buffer[4]) {
            '2' => match ($buffer[5]) {
                'h' => new EnableCursorBlinking(true),
                'l' => new EnableCursorBlinking(false),
                default => throw ParseError::couldNotParseOffset($buffer, 4, 'Could not parse cursor blinking mode'),
            },
            '5' => match ($buffer[5]) {
                'l' => Actions::cursorHide(),
                'h' => Actions::cursorShow(),
                default => throw ParseError::couldNotParseBuffer($buffer),
            },
            '0' => match ($buffer[5]) {
                '4' => (function () use ($buffer): ?AlternateScreenEnable {
                    if (count($buffer) === 6 || count($buffer) === 7) {
                        return null;
                    }

                    return match ($buffer[7]) {
                        'h' => Actions::alternateScreenEnable(),
                        'l' => Actions::alternateScreenDisable(),
                        default => throw ParseError::couldNotParseOffset($buffer, 7),
                    };
                })(),
                default => throw ParseError::couldNotParseOffset($buffer, 5),
            },
            default => throw ParseError::couldNotParseOffset($buffer, 4),
        };
    }

    /**
     * @param string[] $buffer
     */
    private function parseCursorPosition(array $buffer): Action
    {
        $string = implode('', array_slice($buffer, 2, -1));
        $parts = explode(';', $string);
        if (count($parts) !== 2) {
            throw new ParseError(sprintf('Could not parse cursor position from: "%s"', $string));
        }

        return Actions::moveCursor(max(0, (int) ($parts[0])), max(0, (int) ($parts[1])));
    }

    /**
     * @param string[] $buffer
     */
    private function parseClear(array $buffer): Action
    {
        array_shift($buffer);
        array_shift($buffer);
        $clear = implode('', $buffer);

        return new Clear(match ($clear) {
            '2J' => ClearType::All,
            '3J' => ClearType::Purge,
            'J' => ClearType::FromCursorDown,
            '1J' => ClearType::FromCursorUp,
            '2K' => ClearType::CurrentLine,
            'K' => ClearType::UntilNewLine,
            default => throw new ParseError(sprintf(
                'Could not parse clear "%s"',
                $clear
            )),
        });
    }

    private function inverseColorIndex(int $color, bool $background): Colors
    {
        $color -= $background ? 10 : 0;

        return match ($color) {
            30 => Colors::Black,
            31 => Colors::Red,
            32 => Colors::Green,
            33 => Colors::Yellow,
            34 => Colors::Blue,
            35 => Colors::Magenta,
            36 => Colors::Cyan,
            37 => Colors::Gray,
            90 => Colors::DarkGray,
            91 => Colors::LightRed,
            92 => Colors::LightGreen,
            93 => Colors::LightYellow,
            94 => Colors::LightBlue,
            95 => Colors::LightMagenta,
            96 => Colors::LightCyan,
            97 => Colors::White,
            default => throw new ParseError(sprintf('Do not know how to handle color: %s', $color)),
        };
    }

    /**
     * @param string[] $buffer
     */
    private function parseOsc(array $buffer, bool $more): ?Action
    {
        if (count($buffer) === 2) {
            return null;
        }

        return match ($buffer[2]) {
            '0' => $this->parseSetTitle($buffer),
            default => throw new ParseError(sprintf('Could not parse OSC sequence: %s', json_encode(implode('', $buffer)))),
        };

    }

    /**
     * @param string[] $buffer
     */
    private function parseSetTitle(array $buffer): ?Action
    {
        if (count($buffer) <= 3) {
            return null;
        }
        if ($buffer[3] !== ';') {
            throw ParseError::couldNotParseBuffer($buffer, sprintf('Expected ";" after 0 for set title command'));
        }
        $last = $buffer[array_key_last($buffer)];
        if ($last === "\x07") { // BEL
            return new SetTerminalTitle(implode('', array_slice($buffer, 4, -1)));
        }
        return null;
    }

    /**
     * @param string[] $buffer
     */
    private function parseLineWrap(array $buffer): ?Action
    {
        if (count($buffer) === 4) {
            return null;
        }
        $last = $buffer[array_key_last($buffer)];
        return match($last) {
            'h' => new EnableLineWrap(true),
            'l' => new EnableLineWrap(false),
            default => throw ParseError::couldNotParseOffset($buffer, intval(array_key_last($buffer)), 'Could not parse line wrapping'),
        };
    }

    /**
     * @param string[] $buffer
     */
    private function parseCursorMovement(array $buffer): Action
    {
        $type = array_pop($buffer);
        $amount = intval(implode('', array_slice($buffer, 2)));

        return match($type) {
            'E' => new MoveCursorNextLine($amount),
            'F' => new MoveCursorPrevLine($amount),
            'G' => new MoveCursorToColumn($amount - 1),
            'd' => new MoveCursorToRow($amount - 1),
            'A' => new MoveCursorUp($amount),
            'C' => new MoveCursorRight($amount),
            'B' => new MoveCursorDown($amount),
            'D' => new MoveCursorLeft($amount),
            default => throw ParseError::couldNotParseBuffer($buffer, 'Could not parse cursor movement'),
        };
    }

    /**
     * @param string[] $buffer
     */
    private function parseCursorStyle(array $buffer): Action
    {
        $number = intval(trim(implode('', array_slice($buffer, 2, -1))));
        return new SetCursorStyle(match($number) {
            0 => CursorStyle::DefaultUserShape,
            1 => CursorStyle::BlinkingBlock,
            2 => CursorStyle::SteadyBlock,
            3 => CursorStyle::BlinkingUnderScore,
            4 => CursorStyle::SteadyUnderScore,
            5 => CursorStyle::BlinkingBar,
            6 => CursorStyle::SteadyBar,
            default => throw ParseError::couldNotParseBuffer($buffer, 'Could not parse cursor style'),
        });
    }

    /**
     * @param string[] $buffer
     */
    private function parseScroll(array $buffer): Action
    {
        $type = array_pop($buffer);
        $amount = intval(implode('', array_slice($buffer, 2)));

        return match($type) {
            'S' => new ScrollUp($amount),
            'T' => new ScrollDown($amount),
            default => throw ParseError::couldNotParseBuffer($buffer, 'Could not parse scroll'),
        };
    }
}
