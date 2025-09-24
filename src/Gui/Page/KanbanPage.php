<?php

declare(strict_types=1);

namespace Lpuygrenier\Lazykanban\Gui\Page;

use Lpuygrenier\Lazykanban\Entity\Board;
use Lpuygrenier\Lazykanban\Entity\Task;
use Lpuygrenier\Lazykanban\Gui\Constant\Colors;
use Lpuygrenier\Lazykanban\Gui\Common\KeyboardAction;
use Lpuygrenier\Lazykanban\Gui\Common\IGuiComponent;
use Lpuygrenier\Lazykanban\Constants\Keybinds;
use Lpuygrenier\Lazykanban\Gui\Component\TaskComponent;
use Lpuygrenier\Lazykanban\Gui\Component\BoardComponent;
use Lpuygrenier\Lazykanban\Gui\Component\BoardSectionComponent;
use Lpuygrenier\Lazykanban\Gui\Component\TaskForm;
use Lpuygrenier\Lazykanban\Gui\Component\BoardForm;
use Lpuygrenier\Lazykanban\Gui\Component\Input;
use Lpuygrenier\Lazykanban\Gui\Component\TaskDescription;
use Lpuygrenier\Lazykanban\Gui\Page\State\KanbanPageState;
use Lpuygrenier\Lazykanban\Gui\Page\State\ViewingState;
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
    private ?Input $filterInput = null;
    private KanbanPageState $state;
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

        // Initialize filter input
        $this->filterInput = new Input();
        $this->filterInput->setLabel('Filter query');
        $this->filterInput->setOnSubmit(function(string $query) {
            $this->applyFilter($query);
        });
        $this->filterInput->setOnCancel(function() {
            $this->clearFilter();
        });

        // Initialize state
        $this->state = new ViewingState();

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
        $this->state = new ViewingState();
    }

    private function cancelTaskForm(): void
    {
        $this->state = new ViewingState();
    }

    private function submitBoard(string $name, $editingBoard = null): void
    {
        if (!empty($name)) {
            if ($this->onBoardCreate !== null) {
                ($this->onBoardCreate)($name, $editingBoard);
            }
        }
        $this->state = new ViewingState();
    }

    private function cancelBoardForm(): void
    {
        $this->state = new ViewingState();
    }

    private function applyFilter(string $query): void
    {
        $filterCallable = function ($item) use ($query) {
            if ($this->activeComponent === 'task') {
                // For tasks, item is ['task' => Task, 'status' => string]
                $task = $item['task'];
                return stripos($task->getName(), $query) !== false || stripos((string)$task->getId(), $query) !== false;
            } elseif ($this->activeComponent === 'boardsection') {
                // For board files, item is string
                return stripos($item, $query) !== false;
            }
            return true;
        };

        if ($this->activeComponent === 'task') {
            $this->taskComponent->setFilter($filterCallable);
        } elseif ($this->activeComponent === 'boardsection') {
            $this->boardSectionComponent->setFilter($filterCallable);
        }

        $this->state = new ViewingState();
    }

    private function clearFilter(): void
    {
        if ($this->activeComponent === 'task') {
            $this->taskComponent->clearFilter();
        } elseif ($this->activeComponent === 'boardsection') {
            $this->boardSectionComponent->clearFilter();
        }

        $this->state = new ViewingState();
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

    public function getTaskForm(): TaskForm
    {
        return $this->taskForm;
    }

    public function getBoardForm(): BoardForm
    {
        return $this->boardForm;
    }

    public function getTaskComponent(): TaskComponent
    {
        return $this->taskComponent;
    }

    public function getBoardSectionComponent(): BoardSectionComponent
    {
        return $this->boardSectionComponent;
    }

    public function getFilterInput(): Input
    {
        return $this->filterInput;
    }

    public function getActiveComponent(): string
    {
        return $this->activeComponent;
    }

    public function setActiveComponent(string $component): void
    {
        $this->activeComponent = $component;
    }

    public function getCurrentComponent(): IGuiComponent
    {
        if ($this->activeComponent === 'task') {
            return $this->taskComponent;
        } elseif ($this->activeComponent === 'boardsection') {
            return $this->boardSectionComponent;
        }
        return $this->taskComponent;
    }


    public function build(): Widget
    {
        // Show filter input if active
        if ($this->state->isFiltering($this) && $this->filterInput !== null) {
            return $this->filterInput->build();
        }

        // Show task form if active
        if ($this->state->isEditingTask($this) && $this->taskForm !== null) {
            return $this->taskForm->build();
        }

        // Show board form if active
        if ($this->state->isEditingBoard($this) && $this->boardForm !== null) {
            return $this->boardForm->build();
        }

        $this->taskComponent->setActive($this->activeComponent === 'task');
        $this->boardComponent->setActive($this->activeComponent === 'board');
        $this->boardComponent->setSelectedTaskIndex($this->taskComponent->getState()->selected);
        $this->boardSectionComponent->setActive($this->activeComponent === 'boardsection');


        $taskDescription = new TaskDescription($this->getCurrentTask()->getDescription() ?? '');

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
            ->direction(Direction::Vertical)
            ->constraints(
                Constraint::percentage(75),
                Constraint::percentage(25),
            )
            ->widgets(
                $this->boardComponent->build(),
                $taskDescription->build()
            );
            
        $allContent = GridWidget::default()
            ->direction(Direction::Horizontal)
            ->constraints(
                Constraint::percentage(25),
                Constraint::percentage(75),
            )
            ->widgets(
                $sideContent,
                $mainContent
            );
        
        return $allContent;
    }


    public function handleKeybindAction(KeyboardAction $keyboardAction): void {
        if ($this->state instanceof EditingTaskState) {
            $this->taskForm->handleKeybindAction($keyboardAction);
        } elseif ($this->state instanceof EditingBoardState) {
            $this->boardForm->handleKeybindAction($keyboardAction);
        } elseif ($this->state instanceof FilteringState) {
            $this->filterInput->handleKeybindAction($keyboardAction);
        } else {
            $newState = $this->state->handleKeybindAction($this, $keyboardAction);
            if ($newState !== null) {
                $this->state = $newState;
            }
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

    private function getCurrentTask(): ?Task
    {
        $filteredTasks = $this->taskComponent->getFilteredItems();
        $selectedIndex = $this->taskComponent->getState()->selected;
        $task = null;
        if (isset($filteredTasks[$selectedIndex])) {
            $task = $filteredTasks[$selectedIndex]['task'];
        }
        return $task;
    }
}
