<?php

declare(strict_types=1);

namespace Lpuygrenier\Lazykanban\Gui\Component;

use Lpuygrenier\Lazykanban\Gui\Constant\Colors;
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
    private bool $isActive = false;
    private $onBoardSelected = null;

    public function __construct(array $boardFiles, int $boardSelected)
    {
        $this->boardFiles = $boardFiles;
        $this->boardSelected = $boardSelected;
    }

    public function setOnBoardSelected(callable $callback): void
    {
        $this->onBoardSelected = $callback;
    }

    public function setActive(bool $active): void
    {
        $this->isActive = $active;
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
            $this->triggerBoardSelection();
        }
    }

    public function moveDown(): void
    {
        if ($this->boardSelected < count($this->boardFiles) - 1) {
            $this->boardSelected++;
            $this->triggerBoardSelection();
        }
    }

    private function triggerBoardSelection(): void
    {
        if ($this->onBoardSelected !== null && isset($this->boardFiles[$this->boardSelected])) {
            ($this->onBoardSelected)($this->boardFiles[$this->boardSelected]);
        }
    }

    public function build(): Widget
    {
        if (empty($this->boardFiles)) {
            return BlockWidget::default()
                ->borders(Borders::ALL)
                ->borderStyle(Style::default()->fg($this->isActive ? Colors::$GREEN : Colors::$GREY))
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

        $widget = BlockWidget::default()
            ->borders(Borders::ALL)
            ->borderStyle(Style::default()->fg($this->isActive ? Colors::$GREEN : Colors::$GREY))
            ->titles(Title::fromString('Boards'))
            ->widget(
                TableWidget::default()
                    ->state($boardState)
                    ->highlightSymbol('>')
                    ->highlightStyle(Style::default()->fg(Colors::$BLACK)->bg(Colors::$CYAN))
                    ->widths(Constraint::percentage(100))
                    ->rows(...$boardRows)
            );

        return $widget;
    }

    public function handleKeybindAction(KeyboardAction $keyboardAction): void
    {
        $action = $keyboardAction->getAction();
        if ($action === null) {
            return;
        }

        switch ($action) {
            case 'move_up':
                $this->moveUp();
                break;
            case 'move_down':
                $this->moveDown();
                break;
        }
    }
}