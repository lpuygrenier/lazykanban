<?php

declare(strict_types=1);

namespace Lpuygrenier\Lazykanban\Gui\Component;

use Lpuygrenier\Lazykanban\Constants\Keybinds;
use Lpuygrenier\Lazykanban\Gui\Constant\Colors;
use Lpuygrenier\Lazykanban\Gui\Constant\Widgets;
use Lpuygrenier\Lazykanban\Gui\Common\KeyboardAction;
use Lpuygrenier\Lazykanban\Gui\Common\IGuiComponent;
use PhpTui\Tui\Extension\Core\Widget\BlockWidget;
use PhpTui\Tui\Extension\Core\Widget\GridWidget;
use PhpTui\Tui\Extension\Core\Widget\ParagraphWidget;
use PhpTui\Tui\Extension\Core\Widget\Table\TableCell;
use PhpTui\Tui\Extension\Core\Widget\Table\TableRow;
use PhpTui\Tui\Extension\Core\Widget\Table\TableState;
use PhpTui\Tui\Extension\Core\Widget\TableWidget;
use PhpTui\Tui\Layout\Constraint;
use PhpTui\Tui\Style\Style;
use PhpTui\Tui\Text\Text;
use PhpTui\Tui\Text\Title;
use PhpTui\Tui\Widget\Borders;
use PhpTui\Tui\Widget\BorderType;
use PhpTui\Tui\Widget\Direction;
use PhpTui\Tui\Widget\Widget;
use PhpTui\Term\Event\CodedKeyEvent;
use PhpTui\Term\KeyCode;

final class HelpComponent implements IGuiComponent
{
    /** @var KeyboardAction[] */
    private array $keybinds;
    private $onCancel;

    public function __construct(array $keybinds, callable $onCancel)
    {
        $this->keybinds = $keybinds;
        $this->onCancel = $onCancel;
    }

    public function build(): Widget
    {
        $actionToKey = Keybinds::getActionToKey();

        $rows = [];
        foreach ($this->keybinds as $keybind) {
            $action = $keybind->getAction();
            $description = $keybind->getDescription();
            $key = $actionToKey[$action] ?? 'Unknown';

            $rows[] = TableRow::fromCells(
                TableCell::fromString($action),
                TableCell::fromString($key),
                TableCell::fromString($description ?? 'No description')
            );
        }

        $table = TableWidget::default()
            ->header(
                TableRow::fromCells(
                    TableCell::fromString('Action'),
                    TableCell::fromString('Key'),
                    TableCell::fromString('Description')
                )
            )
            ->widths(
                Constraint::percentage(25),
                Constraint::percentage(15),
                Constraint::percentage(60)
            )
            ->rows(...$rows);

        $content = BlockWidget::default()
            ->borders(Borders::ALL)
            ->borderType(BorderType::Rounded)
            ->borderStyle(Style::default()->fg(Colors::$BLUE))
            ->titles(Title::fromString('Help - Press Escape to close'))
            ->widget($table);

        return GridWidget::default()
            ->direction(Direction::Vertical)
            ->constraints(
                Constraint::percentage(20), // top
                Constraint::percentage(60), // content
                Constraint::percentage(20)  // bottom
            )
            ->widgets(
                Widgets::$EMPTY,
                GridWidget::default()
                    ->direction(Direction::Horizontal)
                    ->constraints(
                        Constraint::percentage(10), // left
                        Constraint::percentage(80), // content
                        Constraint::percentage(10)  // right
                    )
                    ->widgets(
                        Widgets::$EMPTY,
                        $content,
                        Widgets::$EMPTY
                    ),
                Widgets::$EMPTY
            );
    }

    public function handleKeybindAction(KeyboardAction $keyboardAction): void
    {
        $event = $keyboardAction->getEvent();

        // Handle Escape to cancel
        if ($event instanceof CodedKeyEvent && $event->code === KeyCode::Esc) {
            if ($this->onCancel) {
                ($this->onCancel)();
            }
        }
    }

    public function getKeybindActions(): array
    {
        return [];
    }
}