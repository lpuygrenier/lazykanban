<?php
namespace Lpuygrenier\Lazykanban\Gui\Constant;

use PhpTui\Tui\Style\Style;

final class Styles
{
    public static $HIGHLIGHTED_STYLE;
    public static $HIGHLIGHTED_SYMBOL;
    public static $TODO_SYMBOL;
    public static $IN_PROGRESS_SYMBOL;
    public static $DONE_SYMBOL;

}

Styles::$HIGHLIGHTED_STYLE = Style::default()->fg(Colors::$WHITE)->bg(Colors::$BLUE);
Styles::$HIGHLIGHTED_SYMBOL = ' *';

// It's breaking UI :(
// Styles::$TODO_SYMBOL = '📝';
// Styles::$IN_PROGRESS_SYMBOL = '🛠️';
// Styles::$DONE_SYMBOL = '✅';
Styles::$TODO_SYMBOL = 'Todo';
Styles::$IN_PROGRESS_SYMBOL = 'Pend.';
Styles::$DONE_SYMBOL = 'Ok';