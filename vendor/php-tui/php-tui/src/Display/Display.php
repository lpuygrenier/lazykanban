<?php

declare(strict_types=1);

namespace PhpTui\Tui\Display;

use PhpTui\Tui\Display\Viewport\Fullscreen;
use PhpTui\Tui\Display\Viewport\Inline;
use PhpTui\Tui\Position\Position;
use PhpTui\Tui\Widget\Widget;
use PhpTui\Tui\Widget\WidgetRenderer;
use PhpTui\Tui\Widget\WidgetRenderer\NullWidgetRenderer;

final class Display
{
    /**
     * @param array<int,Buffer> $buffers
     */
    public function __construct(
        private readonly Backend $backend,
        private array $buffers,
        private int $current,
        /** @phpstan-ignore-next-line */
        private readonly bool $hiddenCursor,
        private readonly Viewport $viewport,
        private Area $viewportArea,
        private Area $lastKnownSize,
        private Position $lastKnownCursorPosition,
        private readonly WidgetRenderer $widgetRenderer,
    ) {
    }

    public static function new(
        Backend $backend,
        Viewport $viewport,
        WidgetRenderer $renderer,
    ): self {
        $size = $viewport->size($backend);
        $cursorPos = $viewport->cursorPos($backend);
        $viewportArea = $viewport->area($backend, 0);

        return new self(
            $backend,
            [Buffer::empty($viewportArea), Buffer::empty($viewportArea)],
            0,
            false,
            $viewport,
            $viewportArea,
            $size,
            $cursorPos,
            $renderer,
        );
    }

    public function flush(): void
    {
        $previous = $this->buffers[1 - $this->current];
        $current = $this->buffers[$this->current];
        $updates = $previous->diff($current);
        if (count($updates) > 0) {
            $this->lastKnownCursorPosition = $updates->last()->position;
        }

        $this->backend->draw($updates);
    }

    public function buffer(): Buffer
    {
        return $this->buffers[$this->current];
    }

    public function clear(): void
    {
        $this->viewport->clear($this->backend, $this->viewportArea);
        // Reset the back buffer to make sure the next update will redraw everything.
        $this->buffers[1 - $this->current] = Buffer::empty($this->viewportArea);
    }

    /**
     * Synchronizes terminal size, renders the given widget, flushes the
     * current internal state and prepares for the next draw call.
     */
    public function draw(Widget $widget): void
    {
        $this->autoresize();
        $buffer = $this->buffer();
        $this->widgetRenderer->render(
            new NullWidgetRenderer(),
            $widget,
            $buffer,
            $buffer->area()
        );

        $this->flush();
        $this->backend->flush();
        $this->swapBuffers();
    }

    public function viewportArea(): Area
    {
        return $this->viewportArea;
    }

    /**
     * Render a widget before the current inline viewport. This has no effect when the
     * viewport is fullscreen.
     *
     * This function scrolls down the current viewport by the given height. The
     * newly freed space is then made available to the draw call.
     *
     * Before:
     *
     * ```
     * +-------------------+
     * |                   |
     * |      viewport     |
     * |                   |
     * +-------------------+
     * ```

     * After:
     *
     * ```
     * +-------------------+
     * |      buffer       |
     * +-------------------+
     * +-------------------+
     * |                   |
     * |      viewport     |
     * |                   |
     * +-------------------+
     * ```
     * @param int<0,max> $height
     */
    public function insertBefore(int $height, Widget $widget): void
    {
        if (!$this->viewport instanceof Inline) {
            return;
        }

        $height = min($height, $this->lastKnownSize->height);
        $missingLines = max(0, $height - $this->lastKnownSize->bottom() - $this->viewportArea->top());

        $area = Area::fromScalars(
            $this->viewportArea->left(),
            max(0, $this->viewportArea->top() - $missingLines),
            $this->viewportArea->width,
            $height
        );
        $buffer = Buffer::empty($area);
        $this->widgetRenderer->render(
            new NullWidgetRenderer(),
            $widget,
            $buffer,
            $buffer->area(),
        );

        $this->backend->draw($buffer->toUpdates());
        $this->backend->flush();

        $remainingLines = $this->lastKnownSize->height - $area->bottom();
        $missingLines = max(0, $this->viewportArea->height - $remainingLines);
        $this->backend->appendLines($this->viewportArea->height);
        $this->setViewportArea(Area::fromScalars(
            $area->left(),
            max(0, $area->bottom() - $missingLines),
            $area->width,
            $this->viewportArea->height
        ));
    }

    private function autoresize(): void
    {
        if (!$this->viewport instanceof Fullscreen && !$this->viewport instanceof Inline) {
            return;
        }

        $size = $this->backend->size();
        if ($size == $this->lastKnownSize) {
            return;
        }

        $this->resize($size);
    }

    private function resize(Area $size): void
    {
        $offsetInPreviousViewport = max(0, $this->lastKnownCursorPosition->y - $this->viewportArea->top());

        $size = $this->viewport->size($this->backend);
        $nextArea = $this->viewport->area($this->backend, $offsetInPreviousViewport);

        $this->setViewportArea($nextArea);
        $this->clear();
        $this->lastKnownSize = $size;
    }

    private function setViewportArea(Area $area): void
    {
        $this->buffers[$this->current] = Buffer::empty($area);
        $this->buffers[1 - $this->current] = Buffer::empty($area);
        $this->viewportArea = $area;
    }

    private function swapBuffers(): void
    {
        $this->buffers[1 - $this->current] = Buffer::empty($this->viewportArea);
        $this->current = 1 - $this->current;
    }
}
