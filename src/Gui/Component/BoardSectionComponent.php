<?php

declare(strict_types=1);

namespace Lpuygrenier\Lazykanban\Gui\Component;

use Lpuygrenier\Lazykanban\Gui\Constant\Colors;
use Lpuygrenier\Lazykanban\Gui\Constant\Styles;
use Lpuygrenier\Lazykanban\Gui\KeyboardAction;
use Lpuygrenier\Lazykanban\Gui\Common\IGuiComponent;
use Lpuygrenier\Lazykanban\Gui\Common\IFilterable;
use Lpuygrenier\Lazykanban\Constants\Keybinds;
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
use PhpTui\Tui\Widget\BorderType;
use PhpTui\Tui\Widget\Widget;

final class BoardSectionComponent implements IGuiComponent, IFilterable
{
    private array $boardFiles;
    private int $boardSelected;
    private bool $isActive = false;
    private $onBoardSelected = null;
    private mixed $filter = null;

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

    public function updateBoardFiles(array $boardFiles): void
    {
        $this->boardFiles = $boardFiles;
        // Reset selection if it's out of bounds
        $filteredCount = count($this->getFilteredItems());
        if ($this->boardSelected >= $filteredCount) {
            $this->boardSelected = max(0, $filteredCount - 1);
        }
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
        $filteredCount = count($this->getFilteredItems());
        if ($this->boardSelected < $filteredCount - 1) {
            $this->boardSelected++;
            $this->triggerBoardSelection();
        }
    }

    private function triggerBoardSelection(): void
    {
        $filteredItems = $this->getFilteredItems();
        if ($this->onBoardSelected !== null && isset($filteredItems[$this->boardSelected])) {
            ($this->onBoardSelected)($filteredItems[$this->boardSelected]);
        }
    }

    public function build(): Widget
    {
        $filteredFiles = $this->getFilteredItems();
        if (empty($filteredFiles)) {
            return BlockWidget::default()
                ->borders(Borders::ALL)
                ->borderType(BorderType::Rounded)
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
        }, $filteredFiles);

        $boardState = new TableState(selected: $this->boardSelected);

        $highlightStyle = $this->isActive ? Styles::$HIGHLIGHTED_STYLE : Style::default();

        $widget = BlockWidget::default()
            ->borders(Borders::ALL)
            ->borderType(BorderType::Rounded)
            ->borderStyle(Style::default()->fg($this->isActive ? Colors::$GREEN : Colors::$GREY))
            ->titles(Title::fromString('Boards'))
            ->widget(
                TableWidget::default()
                    ->state($boardState)
                    ->highlightSymbol(Styles::$HIGHLIGHTED_SYMBOL)
                    ->highlightStyle($highlightStyle)
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
            case Keybinds::ACTION_MOVE_UP:
                $this->moveUp();
                break;
            case Keybinds::ACTION_MOVE_DOWN:
                $this->moveDown();
                break;
        }
    }

    public function getKeybindActions(): array
    {
        $descriptions = Keybinds::getDescriptions();
        return [
            new KeyboardAction(Keybinds::ACTION_MOVE_UP, null, $descriptions[Keybinds::ACTION_MOVE_UP]),
            new KeyboardAction(Keybinds::ACTION_MOVE_DOWN, null, $descriptions[Keybinds::ACTION_MOVE_DOWN]),
        ];
    }

    public function setFilter(?callable $filter): void
    {
        $this->filter = $filter;
        $this->boardSelected = 0;
    }

    public function clearFilter(): void
    {
        $this->filter = null;
        $this->boardSelected = 0;
    }

    public function getFilteredItems(): array
    {
        if ($this->filter === null) {
            return $this->boardFiles;
        }

        return array_filter($this->boardFiles, $this->filter);
    }
}