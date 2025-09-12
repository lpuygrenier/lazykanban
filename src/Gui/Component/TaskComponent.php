<?php

declare(strict_types=1);

namespace Lpuygrenier\Lazykanban\Gui\Component;

use Lpuygrenier\Lazykanban\Entity\Board;
use Lpuygrenier\Lazykanban\Entity\Status;
use Lpuygrenier\Lazykanban\Gui\KeyboardAction;
use Lpuygrenier\Lazykanban\Gui\GuiComponent;
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
use PhpTui\Tui\Widget\Widget;

final class TaskComponent implements GuiComponent
{
    private Board $board;
    private TableState $state;
    private bool $isActive = false;

    public function __construct(Board $board, TableState $state)
    {
        $this->board = $board;
        $this->state = $state;
    }

    public function setActive(bool $active): void
    {
        $this->isActive = $active;
    }

    public function getState(): TableState
    {
        return $this->state;
    }

    public function moveUp(): void
    {
        $totalTasks = count($this->board->todo) + count($this->board->inProgress) + count($this->board->done);
        if ($this->state->selected > 0) {
            $this->state->selected--;
        }
    }

    public function moveDown(): void
    {
        $totalTasks = count($this->board->todo) + count($this->board->inProgress) + count($this->board->done);
        if ($this->state->selected < $totalTasks - 1) {
            $this->state->selected++;
        }
    }

    public function build(): Widget
    {
        $widget = BlockWidget::default()
            ->borders(Borders::ALL)
            ->titles(Title::fromString('Tasks'))
            ->widget($this->taskTable());

        if ($this->isActive) {
            $widget = $widget->borderStyle(Style::default()->fg(Color::Green));
        }

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
            case 'move_task':
                $this->moveSelectedTask();
                break;
            case 'delete_task':
                $this->deleteSelectedTask();
                break;
        }
    }

    public function moveSelectedTask(): void
    {
        $allTasks = array_merge(
            array_map(fn($task) => ['task' => $task, 'status' => 'TODO'], $this->board->todo),
            array_map(fn($task) => ['task' => $task, 'status' => 'IN_PROGRESS'], $this->board->inProgress),
            array_map(fn($task) => ['task' => $task, 'status' => 'DONE'], $this->board->done)
        );

        if (isset($allTasks[$this->state->selected])) {
            $task = $allTasks[$this->state->selected]['task'];
            $status = $allTasks[$this->state->selected]['status'];
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
        $allTasks = array_merge(
            array_map(fn($task) => ['task' => $task, 'status' => 'TODO'], $this->board->todo),
            array_map(fn($task) => ['task' => $task, 'status' => 'IN_PROGRESS'], $this->board->inProgress),
            array_map(fn($task) => ['task' => $task, 'status' => 'DONE'], $this->board->done)
        );

        if (isset($allTasks[$this->state->selected])) {
            $task = $allTasks[$this->state->selected]['task'];
            $this->board->remove($task);
            // Adjust selection if necessary
            $totalTasks = count($this->board->todo) + count($this->board->inProgress) + count($this->board->done);
            if ($this->state->selected >= $totalTasks) {
                $this->state->selected = max(0, $totalTasks - 1);
            }
        }
    }

    private function taskTable(): TableWidget
    {
        // Get all tasks from the board
        $allTasks = array_merge(
            array_map(fn($task) => ['task' => $task, 'status' => 'TODO'], $this->board->todo),
            array_map(fn($task) => ['task' => $task, 'status' => 'IN_PROGRESS'], $this->board->inProgress),
            array_map(fn($task) => ['task' => $task, 'status' => 'DONE'], $this->board->done)
        );

        return TableWidget::default()
            ->state($this->state)
            ->highlightSymbol('X')
            ->highlightStyle(Style::default()->black()->onCyan())
            ->widths(
                Constraint::percentage(10),
                Constraint::percentage(50),
                Constraint::percentage(30),
            )
            ->header(
                TableRow::fromCells(
                    TableCell::fromString('ID'),
                    TableCell::fromString('Task'),
                    TableCell::fromString('Status'),
                )
            )
            ->rows(...array_map(function (array $taskData) {
                $task = $taskData['task'];
                $status = $taskData['status'];
                return TableRow::fromCells(
                    TableCell::fromString((string)$task->getId()),
                    TableCell::fromString($task->getName()),
                    TableCell::fromString($status)
                );
            }, $allTasks));
    }
}