<?php

namespace Lpuygrenier\Lazykanban\Gui\Common;

use Lpuygrenier\Lazykanban\Gui\Common\KeyboardAction;

interface IInteractive
{
    public function handleKeybindAction(KeyboardAction $keyboardAction): void;
}