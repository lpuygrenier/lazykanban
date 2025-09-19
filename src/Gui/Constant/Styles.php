<?php
namespace Lpuygrenier\Lazykanban\Gui\Constant;

use PhpTui\Tui\Style\Style;

final class Styles
{
    public static $HIGHLIGHTED_STYLE;
    public static $HIGHLIGHTED_SYMBOL;

}

Styles::$HIGHLIGHTED_STYLE = Style::default()->fg(Colors::$WHITE)->bg(Colors::$BLUE);
Styles::$HIGHLIGHTED_SYMBOL = ' *';


