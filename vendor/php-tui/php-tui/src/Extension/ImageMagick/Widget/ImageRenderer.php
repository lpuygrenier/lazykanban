<?php

declare(strict_types=1);

namespace PhpTui\Tui\Extension\ImageMagick\Widget;

use Imagick;
use PhpTui\Tui\Canvas\Marker;
use PhpTui\Tui\Display\Area;
use PhpTui\Tui\Display\Buffer;
use PhpTui\Tui\Extension\Core\Widget\CanvasWidget;
use PhpTui\Tui\Extension\ImageMagick\ImageRegistry;
use PhpTui\Tui\Extension\ImageMagick\Shape\ImageShape;
use PhpTui\Tui\Widget\Widget;
use PhpTui\Tui\Widget\WidgetRenderer;

final class ImageRenderer implements WidgetRenderer
{
    public function __construct(private readonly ImageRegistry $registry)
    {
    }

    public function render(WidgetRenderer $renderer, Widget $widget, Buffer $buffer, Area $area): void
    {
        if (!$widget instanceof ImageWidget) {
            return;
        }

        if (class_exists(Imagick::class)) {
            $image = $this->registry->load($widget->path);
            $geo = $image->getImageGeometry();
        } else {
            // otherwise extension not loaded, image shape will show a
            // placeholder!
            $geo = [ 'width' => 100, 'height' => 100 ];
        }

        $renderer->render($renderer, CanvasWidget::fromIntBounds(
            0,
            $geo['width'] - 1,
            0,
            $geo['height'],
        )->marker($widget->marker ?? Marker::HalfBlock)->draw(ImageShape::fromPath(
            $widget->path
        )), $buffer, $area);
    }
}
