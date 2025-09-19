<?php
namespace Lpuygrenier\Lazykanban\Gui\Constant;

use PhpTui\Tui\Style\Style;

final class Styles
{
    public static $HIGHLIGHTED_STYLE;
    public static $HIGHLIGHTED_SYMBOL;

}

Styles::$HIGHLIGHTED_STYLE = Style::default()->fg(Colors::$BLACK)->bg(Colors::$CYAN);
Styles::$HIGHLIGHTED_SYMBOL = '>';


