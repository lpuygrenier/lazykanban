<?php

declare(strict_types=1);

namespace Lpuygrenier\Lazykanban\Gui\Component;

use Lpuygrenier\Lazykanban\Entity\Board;
use Lpuygrenier\Lazykanban\Entity\Status;
use Lpuygrenier\Lazykanban\Gui\Common\IInteractive;
use Lpuygrenier\Lazykanban\Gui\Common\IKeybindProvider;
use Lpuygrenier\Lazykanban\Gui\Constant\Colors;
use Lpuygrenier\Lazykanban\Gui\Constant\Styles;
use Lpuygrenier\Lazykanban\Gui\Common\KeyboardAction;
use Lpuygrenier\Lazykanban\Gui\Common\IGuiComponent;
use Lpuygrenier\Lazykanban\Gui\Common\IFilterable;
use Lpuygrenier\Lazykanban\Constants\Keybinds;
use PhpTui\Tui\Color\Color;
use PhpTui\Tui\Extension\Core\Widget\BlockWidget;
use PhpTui\Tui\Extension\Core\Widget\GridWidget;
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

final class TaskComponent extends TableComponent implements IGuiComponent, IFilterable
{
    private Board $board;
    private mixed $filter = null;

    public function __construct(Board $board, TableState $state)
    {
        parent::__construct($state, 'Tasks');
        $this->board = $board;
    }

    public function getState(): TableState
    {
        return $this->state;
    }

    protected function getHeaders(): array
    {
        return ['ID', 'Task', 'Status'];
    }

    protected function getRows(): array
    {
        $filteredTasks = $this->getFilteredItems();
        return array_map(function (array $taskData) {
            $task = $taskData['task'];
            $status = $taskData['status'];
            return TableRow::fromCells(
                TableCell::fromString((string)$task->getId()),
                TableCell::fromString($task->getName()),
                TableCell::fromString($this->parseStatus($status)),
            );
        }, $filteredTasks);
    }

    protected function getWidths(): array
    {
        return [10, 70, 20];
    }

    public function moveUp(): void
    {
        if ($this->state->selected > 0) {
            $this->state->selected--;
        }
    }

    public function moveDown(): void
    {
        $totalTasks = count($this->getFilteredItems());
        if ($this->state->selected < $totalTasks - 1) {
            $this->state->selected++;
        }
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
            case Keybinds::ACTION_MOVE_TASK:
                $this->moveSelectedTask();
                break;
            case Keybinds::ACTION_DELETE_TASK:
                $this->deleteSelectedTask();
                break;
        }
    }

    public function getKeybindActions(): array
    {
        $descriptions = Keybinds::getDescriptions();
        return [
            new KeyboardAction(Keybinds::ACTION_MOVE_UP, null, $descriptions[Keybinds::ACTION_MOVE_UP]),
            new KeyboardAction(Keybinds::ACTION_MOVE_DOWN, null, $descriptions[Keybinds::ACTION_MOVE_DOWN]),
            new KeyboardAction(Keybinds::ACTION_MOVE_TASK, null, $descriptions[Keybinds::ACTION_MOVE_TASK]),
            new KeyboardAction(Keybinds::ACTION_DELETE_TASK, null, $descriptions[Keybinds::ACTION_DELETE_TASK]),
        ];
    }

    public function setFilter(?callable $filter): void
    {
        $this->filter = $filter;
        $this->state->selected = 0;
    }

    public function clearFilter(): void
    {
        $this->filter = null;
        $this->state->selected = 0;
    }

    public function getFilteredItems(): array
    {
        $allTasks = array_merge(
            array_map(fn($task) => ['task' => $task, 'status' => 'TODO'], $this->board->todo),
            array_map(fn($task) => ['task' => $task, 'status' => 'IN_PROGRESS'], $this->board->inProgress),
            array_map(fn($task) => ['task' => $task, 'status' => 'DONE'], $this->board->done)
        );

        if ($this->filter === null) {
            return $allTasks;
        }

        return array_filter($allTasks, $this->filter);
    }

    public function moveSelectedTask(): void
    {
        $filteredTasks = $this->getFilteredItems();

        if (isset($filteredTasks[$this->state->selected])) {
            $task = $filteredTasks[$this->state->selected]['task'];
            $status = $filteredTasks[$this->state->selected]['status'];
            $nextStatus = match ($status) {
                'TODO' => Status::IN_PROGRESS,
                'IN_PROGRESS' => Status::DONE,
                'DONE' => Status::TODO,
            };
            $this->board->move($task, $nextStatus);
        }
    }

    public function deleteSelectedTask(): void
    {
        $filteredTasks = $this->getFilteredItems();

        if (isset($filteredTasks[$this->state->selected])) {
            $task = $filteredTasks[$this->state->selected]['task'];
            $this->board->remove($task);
            // Adjust selection if necessary
            $totalTasks = count($this->getFilteredItems());
            if ($this->state->selected >= $totalTasks) {
                $this->state->selected = max(0, $totalTasks - 1);
            }
        }
    }



    private function parseStatus(string $status): string {
        switch ($status) {
            case 'TODO':
                return Styles::$TODO_SYMBOL;
            case 'IN_PROGRESS':
                return Styles::$IN_PROGRESS_SYMBOL;
            case 'DONE':
                return Styles::$DONE_SYMBOL;
        }
        return '';
    }
}