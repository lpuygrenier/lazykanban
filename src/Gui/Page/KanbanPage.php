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

    public function __construct(Board $board, array $boardFiles = [])
    {
        $this->board = $board;
        $this->taskComponent = new TaskComponent($board, new TableState(selected: 0));
        $this->boardComponent = new BoardComponent($board);
        $this->boardSectionComponent = new BoardSectionComponent($boardFiles, 0);
    }


    public function build(): Widget
    {
        $this->taskComponent->setActive($this->activeComponent === 'task');
        $this->boardComponent->setActive($this->activeComponent === 'board');
        $this->boardSectionComponent->setActive($this->activeComponent === 'boardsection');

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
            case 'h':
                $this->activeComponent = 'task';
                break;
            case 'l':
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
                    ->highlightStyle(Style::default()->fg(Colors::BLACK)->bg(Colors::CYAN))
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

}
