<?php

declare(strict_types=1);

namespace Lpuygrenier\Lazykanban\Gui\Page;

use Lpuygrenier\Lazykanban\Entity\Board;
use Lpuygrenier\Lazykanban\Entity\Task;
use Lpuygrenier\Lazykanban\Gui\Constant\Colors;
use Lpuygrenier\Lazykanban\Gui\KeyboardAction;
use Lpuygrenier\Lazykanban\Gui\GuiComponent;
use Lpuygrenier\Lazykanban\Gui\Component\TaskComponent;
use Lpuygrenier\Lazykanban\Gui\Component\BoardComponent;
use Lpuygrenier\Lazykanban\Gui\Component\BoardSectionComponent;
use Lpuygrenier\Lazykanban\Gui\Component\TaskForm;
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
    private ?TaskForm $taskForm = null;
    private bool $isEditingTask = false;
    private string $activeComponent = 'task';
    private $onBoardSwitch = null;
    private $onBoardSave = null;

    public function __construct(Board $board, array $boardFiles = [])
    {
        $this->board = $board;
        $this->taskComponent = new TaskComponent($board, new TableState(selected: 0));
        $this->boardComponent = new BoardComponent($board);
        $this->boardSectionComponent = new BoardSectionComponent($boardFiles, 0);

        // Initialize task form
        $this->taskForm = new TaskForm();
        $this->taskForm->setOnSubmit(function(string $name, string $description, $editingTask = null) {
            $this->submitTask($name, $description, $editingTask);
        });
        $this->taskForm->setOnCancel(function() {
            $this->cancelTaskForm();
        });

        // Set up board selection callback
        $this->boardSectionComponent->setOnBoardSelected(function(string $boardFile) {
            if ($this->onBoardSwitch !== null) {
                ($this->onBoardSwitch)($boardFile);
            }
        });
    }

    public function setOnBoardSwitch(callable $callback): void
    {
        $this->onBoardSwitch = $callback;
    }

    public function setOnBoardSave(callable $callback): void
    {
        $this->onBoardSave = $callback;
    }

    public function updateBoard(Board $newBoard): void
    {
        $this->board = $newBoard;
        $this->taskComponent = new TaskComponent($newBoard, new TableState(selected: 0));
        $this->boardComponent = new BoardComponent($newBoard);
    }

    private function submitTask(string $name, string $description, $editingTask = null): void
    {
        if (!empty($name)) {
            if ($editingTask !== null) {
                // Update existing task
                $this->board->update($editingTask, $name, $description);
            } else {
                // Create new task
                $taskId = $this->board->getNextTaskId();
                $task = new Task($taskId, $name, $description);
                $this->board->add($task);
            }

            // Save changes to file
            if ($this->onBoardSave !== null) {
                ($this->onBoardSave)();
            }
        }
        $this->isEditingTask = false;
    }

    private function cancelTaskForm(): void
    {
        $this->isEditingTask = false;
    }

    private function getSelectedTask()
    {
        $allTasks = array_merge(
            array_map(fn($task) => ['task' => $task, 'status' => 'TODO'], $this->board->todo),
            array_map(fn($task) => ['task' => $task, 'status' => 'IN_PROGRESS'], $this->board->inProgress),
            array_map(fn($task) => ['task' => $task, 'status' => 'DONE'], $this->board->done)
        );

        $selectedIndex = $this->taskComponent->getState()->selected;
        return isset($allTasks[$selectedIndex]) ? $allTasks[$selectedIndex]['task'] : null;
    }


    public function build(): Widget
    {
        // Show task form if active
        if ($this->isEditingTask && $this->taskForm !== null) {
            return $this->taskForm->build();
        }

        $this->taskComponent->setActive($this->activeComponent === 'task');
        $this->boardComponent->setActive($this->activeComponent === 'board');
        $this->boardSectionComponent->setActive($this->activeComponent === 'boardsection');

        $sideContent = GridWidget::default()
            ->direction(Direction::Vertical)
            ->constraints(
                Constraint::percentage(75),
                Constraint::percentage(25),
            )
            ->widgets(
                $this->taskComponent->build(),
                $this->boardSectionComponent->build()
            );

        $mainContent = GridWidget::default()
            ->direction(Direction::Horizontal)
            ->constraints(
                Constraint::percentage(25),
                Constraint::percentage(75),
            )
            ->widgets(
                $sideContent,
                $this->boardComponent->build()
            );
        
        return $mainContent;
    }


    public function handleKeybindAction(KeyboardAction $keyboardAction): void {
        // Handle task form if active
        if ($this->isEditingTask && $this->taskForm !== null) {
            $this->taskForm->handleKeybindAction($keyboardAction);
            return;
        }

        $action = $keyboardAction->getAction();
        if ($action === null) {
            return;
        }

        switch ($action) {
            case 'create_task':
                $this->taskForm->setCreateMode();
                $this->isEditingTask = true;
                break;
            case 'select':
                // Edit selected task
                if ($this->activeComponent === 'task') {
                    $selectedTask = $this->getSelectedTask();
                    if ($selectedTask !== null) {
                        $this->taskForm->setEditMode($selectedTask);
                        $this->isEditingTask = true;
                    }
                }
                break;
            case 'move_left':
                $this->activeComponent = 'task';
                break;
            case 'move_right':
                $this->activeComponent = 'boardsection';
                break;
            case 'move_up':
            case 'move_down':
                if ($this->activeComponent === 'task') {
                    $this->taskComponent->handleKeybindAction($keyboardAction);
                } elseif ($this->activeComponent === 'boardsection') {
                    $this->boardSectionComponent->handleKeybindAction($keyboardAction);
                }
                break;
            case 'move_task':
            case 'delete_task':
                if ($this->activeComponent === 'task') {
                    $this->taskComponent->handleKeybindAction($keyboardAction);
                    // Save changes to file after move/delete operations
                    if ($this->onBoardSave !== null) {
                        ($this->onBoardSave)();
                    }
                }
                break;
        }
    }
}
