<?php

declare(strict_types=1);

namespace Lpuygrenier\Lazykanban\Gui\Common;

use Lpuygrenier\Lazykanban\Gui\Common\KeyboardAction;
use PhpTui\Tui\Widget\Widget;

interface IGuiComponent
{
    public function build(): Widget;

    public function handleKeybindAction(KeyboardAction $keyboardAction): void;

    public function getKeybindActions(): array;
}
