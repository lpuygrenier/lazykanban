<?php

declare(strict_types=1);

namespace Lpuygrenier\Lazykanban\Gui;

use PhpTui\Term\Event;
use PhpTui\Tui\Widget\Widget;

interface GuiComponent
{
    public function build(): Widget;

    public function handleKeybindAction(string $action): void;
}
