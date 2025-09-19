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
    public static $GREY;
}

Colors::$GREEN   = RgbColor::fromRgb(0, 170, 0);
Colors::$WHITE   = RgbColor::fromRgb(170, 170, 170);
Colors::$BLACK   = RgbColor::fromRgb(0, 0, 0);
Colors::$RED     = RgbColor::fromRgb(170, 0, 0);
Colors::$BLUE    = RgbColor::fromRgb(0, 0, 170);
Colors::$YELLOW  = RgbColor::fromRgb(170, 170, 0);
Colors::$CYAN    = RgbColor::fromRgb(0, 170, 170);
Colors::$MAGENTA = RgbColor::fromRgb(170, 0, 170);
Colors::$GREY    = RgbColor::fromRgb(128, 128, 128);
