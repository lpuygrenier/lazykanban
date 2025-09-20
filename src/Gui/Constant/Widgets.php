<?php
namespace Lpuygrenier\Lazykanban\Gui\Constant;

use PhpTui\Tui\Extension\Core\Widget\ParagraphWidget;
use PhpTui\Tui\Text\Text;

final class Widgets 
{
    public static $EMPTY;

}

Widgets::$EMPTY = ParagraphWidget::fromText(Text::fromString(''));