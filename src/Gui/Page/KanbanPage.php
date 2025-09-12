<?php

declare(strict_types=1);

namespace Lpuygrenier\Lazykanban\Gui\Page;

use Lpuygrenier\Lazykanban\Entity\Board;
use Lpuygrenier\Lazykanban\Gui\Constant\Colors;
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
    private string $activeComponent = 'task';
    private $onBoardSwitch = null;

    public function __construct(Board $board, array $boardFiles = [])
    {
        $this->board = $board;
        $this->taskComponent = new TaskComponent($board, new TableState(selected: 0));
        $this->boardComponent = new BoardComponent($board);
        $this->boardSectionComponent = new BoardSectionComponent($boardFiles, 0);

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


    public function build(): Widget
    {
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
        $action = $keyboardAction->getAction();
        if ($action === null) {
            return;
        }

        switch ($action) {
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
