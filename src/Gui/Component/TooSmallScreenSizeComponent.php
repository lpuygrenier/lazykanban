<?php

declare(strict_types=1);

namespace Lpuygrenier\Lazykanban\Gui\Component;

use Lpuygrenier\Lazykanban\Gui\Common\IRenderable;
use Lpuygrenier\Lazykanban\Gui\Constant\Colors;
use PhpTui\Tui\Extension\Core\Widget\BlockWidget;
use PhpTui\Tui\Extension\Core\Widget\ParagraphWidget;
use PhpTui\Tui\Style\Style;
use PhpTui\Tui\Text\Title;
use PhpTui\Tui\Widget\Borders;
use PhpTui\Tui\Widget\BorderType;
use PhpTui\Tui\Widget\Widget;

final class TooSmallScreenSizeComponent implements IRenderable
{

    public function build(): Widget
    {
        return BlockWidget::default()
            ->borders(Borders::ALL)
            ->borderType(BorderType::Rounded)
            ->borderStyle(Style::default()->fg(Colors::$GREY))
            ->titles(Title::fromString('Error'))
            ->widget(ParagraphWidget::fromString('Not enough space to render panels'));
    }
}