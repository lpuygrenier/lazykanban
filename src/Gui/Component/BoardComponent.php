<?php

declare(strict_types=1);

namespace Lpuygrenier\Lazykanban\Gui\Component;

use Lpuygrenier\Lazykanban\Entity\Board;
use Lpuygrenier\Lazykanban\Gui\Constant\Colors;
use Lpuygrenier\Lazykanban\Gui\KeyboardAction;
use Lpuygrenier\Lazykanban\Gui\GuiComponent;
use PhpTui\Tui\Color\Color;
use PhpTui\Tui\Extension\Core\Widget\BlockWidget;
use PhpTui\Tui\Extension\Core\Widget\GridWidget;
use PhpTui\Tui\Extension\Core\Widget\Paragraph\Wrap;
use PhpTui\Tui\Extension\Core\Widget\ParagraphWidget;
use PhpTui\Tui\Layout\Constraint;
use PhpTui\Tui\Style\Style;
use PhpTui\Tui\Text\Text;
use PhpTui\Tui\Text\Title;
use PhpTui\Tui\Widget\Borders;
use PhpTui\Tui\Widget\Direction;
use PhpTui\Tui\Widget\Widget;

final class BoardComponent implements GuiComponent
{
    private Board $board;
    private bool $isActive = false;

    public function __construct(Board $board)
    {
        $this->board = $board;
    }

    public function setActive(bool $active): void
    {
        $this->isActive = $active;
    }

    public function build(): Widget
    {
        $board = $this->kanbanBoard();

        if ($this->isActive) {
            return BlockWidget::default()
                ->borders(Borders::ALL)
                ->borderStyle(Style::default()->fg(Colors::$GREEN))
                ->widget($board);
        } else {
            return $board;
        }
    }

    public function handleKeybindAction(KeyboardAction $keyboardAction): void
    {
        // Board component doesn't handle keyboard actions directly
        // Actions are handled at KanbanPage level
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
}