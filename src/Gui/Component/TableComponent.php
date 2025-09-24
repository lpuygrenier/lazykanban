<?php

namespace Lpuygrenier\Lazykanban\Gui\Component;

use Lpuygrenier\Lazykanban\Gui\Common\IRenderable;
use Lpuygrenier\Lazykanban\Gui\Constant\Colors;
use Lpuygrenier\Lazykanban\Gui\Constant\Styles;
use PhpTui\Tui\Extension\Core\Widget\BlockWidget;
use PhpTui\Tui\Extension\Core\Widget\Table\TableCell;
use PhpTui\Tui\Extension\Core\Widget\Table\TableRow;
use PhpTui\Tui\Extension\Core\Widget\Table\TableState;
use PhpTui\Tui\Extension\Core\Widget\TableWidget;
use PhpTui\Tui\Layout\Constraint;
use PhpTui\Tui\Style\Style;
use PhpTui\Tui\Text\Title;
use PhpTui\Tui\Widget\Borders;
use PhpTui\Tui\Widget\BorderType;
use PhpTui\Tui\Widget\Widget;

abstract class TableComponent implements IRenderable
{
    protected TableState $state;
    protected bool $isActive = false;
    protected string $title;

    public function __construct(TableState $state, string $title)
    {
        $this->state = $state;
        $this->title = $title;
    }

    public function setActive(bool $active): void
    {
        $this->isActive = $active;
    }

    abstract protected function getHeaders(): array;
    abstract protected function getRows(): array;
    abstract protected function getWidths(): array;

    public function build(): Widget
    {
        $rows = $this->getRows();
        $headers = $this->getHeaders();
        $widths = $this->getWidths();

        $highlightStyle = $this->isActive ? Styles::$HIGHLIGHTED_STYLE : Style::default();

        $table = TableWidget::default()
            ->state($this->state)
            ->highlightSymbol(Styles::$HIGHLIGHTED_SYMBOL)
            ->highlightStyle($highlightStyle)
            ->widths(...array_map(fn($w) => Constraint::percentage($w), $widths))
            ->rows(...$rows);

        if (!empty($headers)) {
            $table = $table->header(
                TableRow::fromCells(
                    ...array_map(fn($header) => TableCell::fromString($header), $headers)
                )
            );
        }

        return BlockWidget::default()
            ->borders(Borders::ALL)
            ->borderType(BorderType::Rounded)
            ->borderStyle(Style::default()->fg($this->isActive ? Colors::$GREEN : Colors::$GREY))
            ->titles(Title::fromString($this->title))
            ->widget($table);
    }
}