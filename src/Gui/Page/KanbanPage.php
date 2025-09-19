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
use Lpuygrenier\Lazykanban\Gui\Component\CreateTaskForm;
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
    private ?CreateTaskForm $createTaskForm = null;
    private bool $isCreatingTask = false;
    private string $activeComponent = 'task';
    private $onBoardSwitch = null;

    public function __construct(Board $board, array $boardFiles = [])
    {
        $this->board = $board;
        $this->taskComponent = new TaskComponent($board, new TableState(selected: 0));
        $this->boardComponent = new BoardComponent($board);
        $this->boardSectionComponent = new BoardSectionComponent($boardFiles, 0);

        // Initialize create task form
        $this->createTaskForm = new CreateTaskForm();
        $this->createTaskForm->setOnSubmit(function(string $name, string $description) {
            $this->submitCreateTask($name, $description);
        });
        $this->createTaskForm->setOnCancel(function() {
            $this->cancelCreateTask();
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

    public function updateBoard(Board $newBoard): void
    {
        $this->board = $newBoard;
        $this->taskComponent = new TaskComponent($newBoard, new TableState(selected: 0));
        $this->boardComponent = new BoardComponent($newBoard);
    }

    private function submitCreateTask(string $name, string $description): void
    {
        if (!empty($name)) {
            $taskId = $this->board->countTasks() + 1;
            $task = new Task($taskId, $name, $description);
            $this->board->add($task);
        }
        $this->isCreatingTask = false;
    }

    private function cancelCreateTask(): void
    {
        $this->isCreatingTask = false;
    }


    public function build(): Widget
    {
        // Show create task form if active
        if ($this->isCreatingTask && $this->createTaskForm !== null) {
            return $this->createTaskForm->build();
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
        // Handle create task form if active
        if ($this->isCreatingTask && $this->createTaskForm !== null) {
            $this->createTaskForm->handleKeybindAction($keyboardAction);
            return;
        }

        $action = $keyboardAction->getAction();
        if ($action === null) {
            return;
        }

        switch ($action) {
            case 'create_task':
                $this->isCreatingTask = true;
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
                }
                break;
        }
    }
}
