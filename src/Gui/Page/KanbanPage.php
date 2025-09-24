<?php

declare(strict_types=1);

namespace Lpuygrenier\Lazykanban\Gui\Page;

use Lpuygrenier\Lazykanban\Entity\Board;
use Lpuygrenier\Lazykanban\Entity\Task;
use Lpuygrenier\Lazykanban\Gui\Constant\Colors;
use Lpuygrenier\Lazykanban\Gui\KeyboardAction;
use Lpuygrenier\Lazykanban\Gui\Common\IGuiComponent;
use Lpuygrenier\Lazykanban\Constants\Keybinds;
use Lpuygrenier\Lazykanban\Gui\Component\TaskComponent;
use Lpuygrenier\Lazykanban\Gui\Component\BoardComponent;
use Lpuygrenier\Lazykanban\Gui\Component\BoardSectionComponent;
use Lpuygrenier\Lazykanban\Gui\Component\TaskForm;
use Lpuygrenier\Lazykanban\Gui\Component\BoardForm;
use PhpTui\Tui\Extension\Core\Widget\GridWidget;
use PhpTui\Tui\Extension\Core\Widget\Table\TableState;
use PhpTui\Tui\Layout\Constraint;
use PhpTui\Tui\Widget\Direction;
use PhpTui\Tui\Widget\Widget;

final class KanbanPage implements IGuiComponent
{

    private Board $board;
    private TaskComponent $taskComponent;
    private BoardComponent $boardComponent;
    private BoardSectionComponent $boardSectionComponent;
    private ?TaskForm $taskForm = null;
    private ?BoardForm $boardForm = null;
    private bool $isEditingTask = false;
    private bool $isEditingBoard = false;
    private string $activeComponent = 'task';
    private $onBoardSwitch = null;
    private $onBoardSave = null;
    private $onBoardCreate = null;

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

        // Initialize board form
        $this->boardForm = new BoardForm();
        $this->boardForm->setOnSubmit(function(string $name, $editingBoard = null) {
            $this->submitBoard($name, $editingBoard);
        });
        $this->boardForm->setOnCancel(function() {
            $this->cancelBoardForm();
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

    public function setOnBoardCreate(callable $callback): void
    {
        $this->onBoardCreate = $callback;
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

    private function submitBoard(string $name, $editingBoard = null): void
    {
        if (!empty($name)) {
            if ($this->onBoardCreate !== null) {
                ($this->onBoardCreate)($name, $editingBoard);
            }
        }
        $this->isEditingBoard = false;
    }

    private function cancelBoardForm(): void
    {
        $this->isEditingBoard = false;
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

    public function getBoardSectionSelected(): int
    {
        return $this->boardSectionComponent->getSelected();
    }

    public function updateBoardFiles(array $boardFiles, int $selected = 0): void
    {
        $this->boardSectionComponent->updateBoardFiles($boardFiles);
        $this->boardSectionComponent->setSelected($selected);
    }


    public function build(): Widget
    {
        // Show task form if active
        if ($this->isEditingTask && $this->taskForm !== null) {
            return $this->taskForm->build();
        }

        // Show board form if active
        if ($this->isEditingBoard && $this->boardForm !== null) {
            return $this->boardForm->build();
        }

        $this->taskComponent->setActive($this->activeComponent === 'task');
        $this->boardComponent->setActive($this->activeComponent === 'board');
        $this->boardComponent->setSelectedTaskIndex($this->taskComponent->getState()->selected);
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

        // Handle board form if active
        if ($this->isEditingBoard && $this->boardForm !== null) {
            $this->boardForm->handleKeybindAction($keyboardAction);
            return;
        }

        $action = $keyboardAction->getAction();
        if ($action === null) {
            return;
        }

        switch ($action) {
            case Keybinds::ACTION_CREATE_TASK:
                if ($this->activeComponent === 'task') {
                    $this->taskForm->setCreateMode();
                    $this->isEditingTask = true;
                } elseif ($this->activeComponent === 'boardsection') {
                    $this->boardForm->setCreateMode();
                    $this->isEditingBoard = true;
                }
                break;
            case Keybinds::ACTION_SELECT:
                // Edit selected task
                if ($this->activeComponent === 'task') {
                    $selectedTask = $this->getSelectedTask();
                    if ($selectedTask !== null) {
                        $this->taskForm->setEditMode($selectedTask);
                        $this->isEditingTask = true;
                    }
                } elseif ($this->activeComponent === 'boardsection') {
                    // For now, board editing is not implemented as boards are just files
                    // Could be extended to rename board files in the future
                }
                break;
            case Keybinds::ACTION_MOVE_LEFT:
                $this->activeComponent = 'task';
                break;
            case Keybinds::ACTION_MOVE_RIGHT:
                $this->activeComponent = 'boardsection';
                break;
            case Keybinds::ACTION_MOVE_UP:
            case Keybinds::ACTION_MOVE_DOWN:
                if ($this->activeComponent === 'task') {
                    $this->taskComponent->handleKeybindAction($keyboardAction);
                } elseif ($this->activeComponent === 'boardsection') {
                    $this->boardSectionComponent->handleKeybindAction($keyboardAction);
                }
                break;
            case Keybinds::ACTION_MOVE_TASK:
            case Keybinds::ACTION_DELETE_TASK:
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

    public function getKeybindActions(): array
    {
        $descriptions = Keybinds::getDescriptions();
        return [
            new KeyboardAction(Keybinds::ACTION_CREATE_TASK, null, $descriptions[Keybinds::ACTION_CREATE_TASK]),
            new KeyboardAction(Keybinds::ACTION_SELECT, null, $descriptions[Keybinds::ACTION_SELECT]),
            new KeyboardAction(Keybinds::ACTION_MOVE_LEFT, null, $descriptions[Keybinds::ACTION_MOVE_LEFT]),
            new KeyboardAction(Keybinds::ACTION_MOVE_RIGHT, null, $descriptions[Keybinds::ACTION_MOVE_RIGHT]),
            new KeyboardAction(Keybinds::ACTION_MOVE_UP, null, $descriptions[Keybinds::ACTION_MOVE_UP]),
            new KeyboardAction(Keybinds::ACTION_MOVE_DOWN, null, $descriptions[Keybinds::ACTION_MOVE_DOWN]),
            new KeyboardAction(Keybinds::ACTION_MOVE_TASK, null, $descriptions[Keybinds::ACTION_MOVE_TASK]),
            new KeyboardAction(Keybinds::ACTION_DELETE_TASK, null, $descriptions[Keybinds::ACTION_DELETE_TASK]),
        ];
    }
}
