<?php

declare(strict_types=1);

namespace PhpTui\Tui\Extension\Core;

use PhpTui\Tui\Display\DisplayExtension;
use PhpTui\Tui\Extension\Core\Shape\CirclePainter;
use PhpTui\Tui\Extension\Core\Shape\ClosurePainter;
use PhpTui\Tui\Extension\Core\Shape\LinePainter;
use PhpTui\Tui\Extension\Core\Shape\MapPainter;
use PhpTui\Tui\Extension\Core\Shape\PointsPainter;
use PhpTui\Tui\Extension\Core\Shape\RectanglePainter;
use PhpTui\Tui\Extension\Core\Shape\SpritePainter;
use PhpTui\Tui\Extension\Core\Widget\BarChartRenderer;
use PhpTui\Tui\Extension\Core\Widget\BlockRenderer;
use PhpTui\Tui\Extension\Core\Widget\BufferWidgetRenderer;
use PhpTui\Tui\Extension\Core\Widget\ChartRenderer;
use PhpTui\Tui\Extension\Core\Widget\CompositeRenderer;
use PhpTui\Tui\Extension\Core\Widget\GaugeRenderer;
use PhpTui\Tui\Extension\Core\Widget\GridRenderer;
use PhpTui\Tui\Extension\Core\Widget\ListRenderer;
use PhpTui\Tui\Extension\Core\Widget\ParagraphRenderer;
use PhpTui\Tui\Extension\Core\Widget\ScrollbarRenderer;
use PhpTui\Tui\Extension\Core\Widget\SparklineRenderer;
use PhpTui\Tui\Extension\Core\Widget\TableRenderer;
use PhpTui\Tui\Extension\Core\Widget\TabsRenderer;

final class CoreExtension implements DisplayExtension
{
    public function shapePainters(): array
    {
        return [
            new CirclePainter(),
            new LinePainter(),
            new MapPainter(),
            new PointsPainter(),
            new RectanglePainter(),
            new SpritePainter(),
            new ClosurePainter(),
        ];
    }

    public function widgetRenderers(): array
    {
        return [
            new BlockRenderer(),
            new ParagraphRenderer(),
            new ChartRenderer(),
            new GridRenderer(),
            new ListRenderer(),
            new BufferWidgetRenderer(),
            new TableRenderer(),
            new GaugeRenderer(),
            new BarChartRenderer(),
            new ScrollbarRenderer(),
            new CompositeRenderer(),
            new TabsRenderer(),
            new SparklineRenderer(),
        ];
    }
}
