<?php

declare(strict_types=1);

namespace Lpuygrenier\Lazykanban\Gui\Page;

use Lpuygrenier\Lazykanban\Entity\Board;
use Lpuygrenier\Lazykanban\Entity\Status;
use Lpuygrenier\Lazykanban\Entity\Task;
use Monolog\Logger;
use PhpTui\Term\Event;
use PhpTui\Term\KeyCode;
use Lpuygrenier\Lazykanban\Gui\GuiComponent;
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
use PhpTui\Tui\Text\Line;
use PhpTui\Tui\Text\Span;
use PhpTui\Tui\Text\Text;
use PhpTui\Tui\Text\Title;
use PhpTui\Tui\Widget\Borders;
use PhpTui\Tui\Widget\Direction;
use PhpTui\Tui\Widget\Widget;

final class KanbanPage implements GuiComponent
{

    private TableState $state;
    private Board $board;
    private bool $showPopup = false;
    private string $editingField = 'name';
    private ?Task $editingTask = null;
    private string $nameValue = '';
    private string $descriptionValue = '';

    public function __construct(Board $board)
    {
        $this->state = new TableState(selected: 0);
        $this->board = $board;
    }


    public function build(): Widget
    {
        $grid = GridWidget::default()
            ->direction(Direction::Horizontal)
            ->constraints(
                Constraint::percentage(25),
                Constraint::percentage(75),
            )
            ->widgets(
                // Left panel - Task list
                BlockWidget::default()
                    ->borders(Borders::ALL)
                    ->titles(Title::fromString('Tasks'))
                    ->widget($this->taskTable()),

                // Right panel - Kanban board
                $this->kanbanBoard()
            )
        ;

        return $grid;
    }


    public function handleKeybindAction(string $action): void {
        $totalTasks = count($this->board->todo) + count($this->board->inProgress) + count($this->board->done);
        switch ($action) {
            case 'move_up':
                if ($this->state->selected > 0) {
                    $this->state->selected = $this->state->selected - 1;
                }
                break;
            case 'move_down':
                if ($this->state->selected < $totalTasks - 1) {
                    $this->state->selected = $this->state->selected + 1;
                }
                break;
            case 'move_task':
                $this->moveSelectedTask();
                break;
            case 'delete_task':
                $this->deleteSelectedTask();
                break;
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
                                Constraint::percentage(percentage: 10),
                                Constraint::percentage(percentage: 50),
                                Constraint::percentage(percentage: 30),
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

    private function kanbanBoard(): Widget {
        return GridWidget::default()
            ->direction(Direction::Horizontal)
            ->constraints(
                Constraint::percentage(33),
                Constraint::percentage(33),
                Constraint::percentage(33),
            )
            ->widgets(
                $this->taskColumn('TODO', $this->board->todo),
                $this->taskColumn('IN PROGRESS', $this->board->inProgress),
                $this->taskColumn('DONE', $this->board->done)
            );
    }
    private function taskColumn(string $title, array $tasks): Widget
    {
        $taskWidgets = array_map(fn($task) => $this->taskCard($task), $tasks);

        // If no tasks, show empty message
        if (empty($taskWidgets)) {
            $taskWidgets[] = ParagraphWidget::fromText(
                Text::parse('<fg=darkgray>No tasks</>')
            );
        }

        $columnContent = GridWidget::default()
            ->direction(Direction::Vertical)
            ->constraints(...array_map(fn() => Constraint::length(3), $taskWidgets))
            ->widgets(...$taskWidgets);

        return BlockWidget::default()
            ->borders(Borders::ALL)
            ->titles(Title::fromString($title))
            ->widget($columnContent);
    }

    private function taskCard($task): Widget
    {
        $content = sprintf(
            "%d. %s",
            $task->getId(),
            $task->getName()
        );

        return BlockWidget::default()
            ->borders(Borders::ALL)
            ->widget(
                ParagraphWidget::fromText(
                    Text::parse($content)
                )->wrap(Wrap::Word)
            );
    }

    private function moveSelectedTask(): void
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

    private function deleteSelectedTask(): void
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
}
