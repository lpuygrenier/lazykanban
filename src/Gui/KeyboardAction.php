<?php

declare(strict_types=1);

namespace Lpuygrenier\Lazykanban\Gui;

use PhpTui\Term\Event;

class KeyboardAction {
    private ?string $action;
    private ?Event $event;

    public function __construct(?string $action = null, ?Event $event = null) {
        $this->action = $action;
        $this->event = $event;
    }

    public function getAction(): ?string {
        return $this->action;
    }

    public function getEvent(): ?Event {
        return $this->event;
    }

    public function hasAction(): bool {
        return $this->action !== null;
    }

    public function isEmpty(): bool {
        return $this->action === null;
    }
}