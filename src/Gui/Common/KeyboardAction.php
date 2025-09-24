<?php

declare(strict_types=1);

namespace Lpuygrenier\Lazykanban\Gui\Common;

use PhpTui\Term\Event;
use Lpuygrenier\Lazykanban\Constants\Keybinds;

class KeyboardAction {
    private ?string $action;
    private ?Event $event;
    private ?string $description;

    public function __construct(?string $action = null, ?Event $event = null, ?string $description = null) {
        $this->action = $action;
        $this->event = $event;
        $this->description = $description;
    }

    public function getAction(): ?string {
        return $this->action;
    }

    public function getEvent(): ?Event {
        return $this->event;
    }

    public function getDescription(): ?string {
        return $this->description;
    }

    public function hasAction(): bool {
        return $this->action !== null;
    }

    public function isEmpty(): bool {
        return $this->action === null;
    }

    public static function getKeybindActions(): array {
        $actions = [];
        foreach (Keybinds::getActionToKey() as $action => $key) {
            $actions[] = new self($action, null, Keybinds::getDescriptions()[$key]);
        }
        return $actions;
    }
}