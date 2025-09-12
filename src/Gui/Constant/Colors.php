<?php
namespace Lpuygrenier\Lazykanban\Gui\Constant;

use PhpTui\Tui\Color\RgbColor;

final class Colors
{
    public static $GREEN;
    public static $WHITE;
    public static $BLACK;
    public static $RED;
    public static $BLUE;
    public static $YELLOW;
    public static $CYAN;
    public static $MAGENTA;
}

Colors::$GREEN   = RgbColor::fromRgb(0, 255, 0);
Colors::$WHITE   = RgbColor::fromRgb(255, 255, 255);
Colors::$BLACK   = RgbColor::fromRgb(0, 0, 0);
Colors::$RED     = RgbColor::fromRgb(255, 0, 0);
Colors::$BLUE    = RgbColor::fromRgb(0, 0, 255);
Colors::$YELLOW  = RgbColor::fromRgb(255, 255, 0);
Colors::$CYAN    = RgbColor::fromRgb(0, 255, 255);
Colors::$MAGENTA = RgbColor::fromRgb(255, 0, 255);
