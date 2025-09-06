<?php

declare(strict_types=1);

namespace Lpuygrenier\Lazykanban\Gui\Page;

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

    private int $selected = 0;

    private TableState $state;
    public const EVENTS = [
        ['Event1', 'INFO'],
        ['Event2', 'INFO'],
    ];


    public function __construct()
    {
        $this->state = new TableState();
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
                GridWidget::default()
                    ->direction(Direction::Vertical)
                    ->constraints(...array_map(fn () => Constraint::percentage(100), array_fill(0, 9, true)))
                    ->widgets(
                        $this->borders($this->dummyTable(), Borders::ALL)
                    ),
                GridWidget::default()
                    ->direction(Direction::Vertical)
                    ->constraints(...array_map(fn () => Constraint::percentage(100), array_fill(0, 9, true)))
                    ->widgets(
                        $this->borders($this->dummyBoard(), Borders::ALL)
                    ),
            )
        ;

        return $grid;
    }


    public function handleKeybindAction(string $action): void {
        switch ($action) {
            case 'move_up':
                if ($this->selected > 0) {
                    $this->selected--;
                }
                break;
            case 'move_down':
                $this->selected++;
                break;
        }
    }

    public function dummyTable(): TableWidget
    {
        return TableWidget::default()
                            ->state($this->state)
                            ->select($this->selected)
                            ->highlightSymbol('X')
                            ->highlightStyle(Style::default()->black()->onCyan())
                            ->widths(
                                Constraint::percentage(percentage: 50),
                                Constraint::percentage(percentage: 50),
                            )
                            ->header(
                                TableRow::fromCells(
                                    TableCell::fromString('Level'),
                                    TableCell::fromString('Event'),
                                )
                            )
                            ->rows(...array_map(function (array $event) {
                                return TableRow::fromCells(
                                    TableCell::fromLine(Line::fromSpan(
                                        Span::fromString($event[1]),
                                    )),
                                    TableCell::fromLine(Line::fromString($event[0]))
                                );
                            }, array_merge(self::EVENTS, self::EVENTS)));
    }
    public function dummyRow(): TableRow
    {
        return TableRow::fromStrings("dummy Row");
    }

    public function dummyBoard() {
        return GridWidget::default()
            ->direction(Direction::Horizontal)
            ->constraints(
                Constraint::percentage(33),
                Constraint::percentage(33),
                Constraint::percentage(33),
            )
            ->widgets($this->dummyBoardList(), $this->dummyBoardList(), $this->dummyBoardList());
    }
    public function dummyBoardList(): Widget
    {
        return $this->borders($this->dummyCard(), Borders::ALL);
    }

    public function dummyCard(): Widget
    {
        return $this->borders($this->lorem(), Borders::ALL, false);
    }
    public function lorem(): ParagraphWidget
    {
        $text = 'Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua.';

        return ParagraphWidget::fromText(
            Text::parse(sprintf('<fg=darkgray>%s</>', $text))
        )->wrap(Wrap::Word);
    }

    /**
     * @param int-mask-of<Borders::*> $borders
     */
    public function borders(Widget $paragraph, int $borders, bool $title = true): Widget
    {
        return BlockWidget::default()
            ->borders($borders)
            ->titles($title ?
                Title::fromString(sprintf('Borders::%s', Borders::toString($borders)))
                : Title::fromString(''))
            ->widget($paragraph)
            ;
    }

}
