<?php

namespace Lpuygrenier\Lazykanban\Gui\Common;

use PhpTui\Tui\Widget\Widget;

interface IRenderable
{
    public function build(): Widget;
}