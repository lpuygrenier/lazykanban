<?php

declare(strict_types=1);

namespace PhpTui\Term\Event;

use PhpTui\Term\KeyCode;
use PhpTui\Term\KeyEventKind;
use PhpTui\Term\KeyModifiers;

final class CodedKeyEvent implements KeyEvent
{
    /**
     * @param int-mask-of<KeyModifiers::*> $modifiers
     */
    private function __construct(
        public readonly KeyCode $code,
        public readonly int $modifiers,
        public readonly KeyEventKind $kind
    ) {
    }

    public function __toString(): string
    {
        return sprintf(
            'CodedKeyEvent(code: %s, modifiers: %s, kind: %s)',
            $this->code->name,
            KeyModifiers::toString($this->modifiers),
            $this->kind->name,
        );
    }

    /**
     * @param int-mask-of<KeyModifiers::*> $modifiers
     */
    public static function new(KeyCode $keyCode, int $modifiers = KeyModifiers::NONE, KeyEventKind $kind = KeyEventKind::Press): self
    {
        return new self($keyCode, $modifiers, $kind);
    }
}
