<?php

declare(strict_types=1);

namespace Lpuygrenier\Lazykanban\Gui\Component;

use Lpuygrenier\Lazykanban\Gui\KeyboardAction;
use Lpuygrenier\Lazykanban\Gui\GuiComponent;
use PhpTui\Tui\Extension\Core\Widget\BlockWidget;
use PhpTui\Tui\Extension\Core\Widget\Paragraph\Wrap;
use PhpTui\Tui\Extension\Core\Widget\ParagraphWidget;
use PhpTui\Tui\Extension\Core\Widget\Table\TableCell;
use PhpTui\Tui\Extension\Core\Widget\Table\TableRow;
use PhpTui\Tui\Extension\Core\Widget\Table\TableState;
use PhpTui\Tui\Extension\Core\Widget\TableWidget;
use PhpTui\Tui\Layout\Constraint;
use PhpTui\Tui\Style\Style;
use PhpTui\Tui\Text\Text;
use PhpTui\Tui\Text\Title;
use PhpTui\Tui\Widget\Borders;
use PhpTui\Tui\Widget\Widget;

final class BoardSectionComponent implements GuiComponent
{
    private array $boardFiles;
    private int $boardSelected;

    public function __construct(array $boardFiles, int $boardSelected)
    {
        $this->boardFiles = $boardFiles;
        $this->boardSelected = $boardSelected;
    }

    public function getSelected(): int
    {
        return $this->boardSelected;
    }

    public function setSelected(int $selected): void
    {
        $this->boardSelected = $selected;
    }

    public function moveUp(): void
    {
        if ($this->boardSelected > 0) {
            $this->boardSelected--;
        }
    }

    public function moveDown(): void
    {
        if ($this->boardSelected < count($this->boardFiles) - 1) {
            $this->boardSelected++;
        }
    }

    public function build(): Widget
    {
        if (empty($this->boardFiles)) {
            return BlockWidget::default()
                ->borders(Borders::ALL)
                ->titles(Title::fromString('Boards'))
                ->widget(
                    ParagraphWidget::fromText(
                        Text::parse('<fg=darkgray>No board files found</>')
                    )
                );
        }

        $boardRows = array_map(function ($file) {
            return TableRow::fromCells(
                TableCell::fromString($file)
            );
        }, $this->boardFiles);

        $boardState = new TableState(selected: $this->boardSelected);

        return BlockWidget::default()
            ->borders(Borders::ALL)
            ->titles(Title::fromString('Boards'))
            ->widget(
                TableWidget::default()
                    ->state($boardState)
                    ->highlightSymbol('>')
                    ->highlightStyle(Style::default()->black()->onCyan())
                    ->widths(Constraint::percentage(100))
                    ->rows(...$boardRows)
            );
    }

    public function handleKeybindAction(KeyboardAction $keyboardAction): void
    {
        // Board section doesn't handle keyboard actions directly
        // Actions are handled at KanbanPage level
    }
}