<?php

declare(strict_types=1);

namespace Lpuygrenier\Lazykanban\Gui\Component;

use Lpuygrenier\Lazykanban\Entity\Board;
use Lpuygrenier\Lazykanban\Entity\Status;
use Lpuygrenier\Lazykanban\Entity\Task;
use Lpuygrenier\Lazykanban\Gui\KeyboardAction;
use Monolog\Logger;
use PhpTui\Term\Event;
use PhpTui\Term\Event\CharKeyEvent;
use PhpTui\Term\Event\CodedKeyEvent;
use PhpTui\Term\Event\KeyEvent;
use PhpTui\Term\KeyCode;
use Lpuygrenier\Lazykanban\Gui\GuiComponent;
use PhpTui\Tui\Color\RgbColor;
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
use PhpTui\Tui\Color\Color;
use PhpTui\Tui\Text\Line;
use PhpTui\Tui\Text\Span;
use PhpTui\Tui\Text\Text;
use PhpTui\Tui\Text\Title;
use PhpTui\Tui\Widget\Borders;
use PhpTui\Tui\Widget\Direction;
use PhpTui\Tui\Widget\Widget;

final class Input implements GuiComponent
{

    private $placeholder = "";
    private $label = "label";
    private $text = " ";
    private $cursor = 0;

    private Logger $logger;

    public function __construct(Logger $logger)
    {
        $this->logger = $logger;
    }

    public function build(): Widget
    {
        $before = substr($this->text, 0, $this->cursor);
        $after = substr($this->text, $this->cursor);
        $cursorChar = $this->cursor < strlen($this->text) ? substr($this->text, $this->cursor, 1) : '_';
        $line = Line::fromSpans(
            Span::fromString($before),
                   Span::fromString($cursorChar)->style(Style::default()->bg(RgbColor::fromRgb(255, 255, 255))->fg(color: RgbColor::fromRgb(0, 0, 0))),

            Span::fromString($after),
        );
        return BlockWidget::default()
            ->borders(Borders::ALL)
            ->titles(Title::fromString($this->label))
            ->widget(
                ParagraphWidget::fromText(
                    Text::fromLines($line)
                )->wrap(Wrap::Word)
            );
    }


    public function handleKeybindAction(KeyboardAction $keyboardAction): void {
        $event = $keyboardAction->getEvent();

        $this->logger->info( $event->__tostring());

        if ($event instanceof CharKeyEvent) {
            $this->text = substr($this->text, 0, $this->cursor) . $event->char . substr($this->text, $this->cursor);
            $this->cursor++;
        } elseif ($event instanceof CodedKeyEvent) {
            if ($event->code === KeyCode::Backspace) {
                if ($this->cursor > 0) {
                    $this->text = substr($this->text, 0, $this->cursor - 1) . substr($this->text, $this->cursor);
                    $this->cursor--;
                }
            } elseif ($event->code === KeyCode::Left) {
                if ($this->cursor > 0) {
                    $this->cursor--;
                }
            } elseif ($event->code === KeyCode::Right) {
                if ($this->cursor < strlen($this->text)) {
                    $this->cursor++;
                }
            }
        }
    }

}
