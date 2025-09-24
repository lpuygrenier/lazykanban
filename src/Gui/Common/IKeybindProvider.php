<?php

namespace Lpuygrenier\Lazykanban\Gui\Common;

interface IKeybindProvider
{
    public function getKeybindActions(): array;
}