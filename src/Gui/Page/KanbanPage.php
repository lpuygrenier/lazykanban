<?php

declare(strict_types=1);

namespace Lpuygrenier\Lazykanban\Gui\Page;

use Lpuygrenier\Lazykanban\Entity\Board;
use Lpuygrenier\Lazykanban\Entity\Status;
use Lpuygrenier\Lazykanban\Entity\Task;
use Lpuygrenier\Lazykanban\Gui\KeyboardAction;
use Lpuygrenier\Lazykanban\Gui\GuiComponent;
use Lpuygrenier\Lazykanban\Gui\Component\TaskComponent;
use Lpuygrenier\Lazykanban\Gui\Component\BoardComponent;
use Lpuygrenier\Lazykanban\Gui\Component\BoardSectionComponent;
use PhpTui\Tui\Extension\Core\Widget\GridWidget;
use PhpTui\Tui\Extension\Core\Widget\Table\TableState;
use PhpTui\Tui\Layout\Constraint;
use PhpTui\Tui\Widget\Direction;
use PhpTui\Tui\Widget\Widget;

final class KanbanPage implements GuiComponent
{

    private Board $board;
    private TaskComponent $taskComponent;
    private BoardComponent $boardComponent;
    private BoardSectionComponent $boardSectionComponent;

    public function __construct(Board $board, array $boardFiles = [])
    {
        $this->board = $board;
        $this->taskComponent = new TaskComponent($board, new TableState(selected: 0));
        $this->boardComponent = new BoardComponent($board);
        $this->boardSectionComponent = new BoardSectionComponent($boardFiles, 0);
    }


    public function build(): Widget
    {
        $mainContent = GridWidget::default()
            ->direction(Direction::Horizontal)
            ->constraints(
                Constraint::percentage(25),
                Constraint::percentage(75),
            )
            ->widgets(
                $this->taskComponent->build(),
                $this->boardComponent->build()
            );

        return GridWidget::default()
            ->direction(Direction::Vertical)
            ->constraints(
                Constraint::percentage(75),
                Constraint::percentage(25),
            )
            ->widgets(
                $mainContent,
                $this->boardSectionComponent->build()
            );
    }


    public function handleKeybindAction(KeyboardAction $keyboardAction): void {
        $action = $keyboardAction->getAction();
        if ($action === null) {
            return;
        }

        switch ($action) {
            case 'move_up':
                $this->taskComponent->moveUp();
                $this->boardSectionComponent->moveUp();
                break;
            case 'move_down':
                $this->taskComponent->moveDown();
                $this->boardSectionComponent->moveDown();
                break;
            case 'move_task':
                $this->moveSelectedTask();
                break;
            case 'delete_task':
                $this->deleteSelectedTask();
                break;
        }
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

    private function boardSection(): Widget
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

        $state = $this->taskComponent->getState();
        if (isset($allTasks[$state->selected])) {
            $task = $allTasks[$state->selected]['task'];
            $status = $allTasks[$state->selected]['status'];
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

        $state = $this->taskComponent->getState();
        if (isset($allTasks[$state->selected])) {
            $task = $allTasks[$state->selected]['task'];
            $this->board->remove($task);
            // Adjust selection if necessary
            $totalTasks = count($this->board->todo) + count($this->board->inProgress) + count($this->board->done);
            if ($state->selected >= $totalTasks) {
                $state->selected = max(0, $totalTasks - 1);
            }
        }
    }
}
