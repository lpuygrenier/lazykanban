<?php

declare(strict_types=1);

namespace Lpuygrenier\Lazykanban\Gui\Component;

use Lpuygrenier\Lazykanban\Entity\Board;
use Lpuygrenier\Lazykanban\Entity\Status;
use Lpuygrenier\Lazykanban\Entity\Task;
use Lpuygrenier\Lazykanban\Gui\Component\TextArea\TextEditor;
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
    private TextEditor $textEditor;

    private ?Logger $logger;

    public function __construct(?Logger $logger = null)
    {
        $this->logger = $logger;
        $this->textEditor = TextEditor::fromString("");
    }

    public function build(): Widget
    {
        $lines = $this->textEditor->viewportLines(0, $this->textEditor->lineCount());
        $cursorPos = $this->textEditor->cursorPosition();
        $displayLines = [];
        foreach ($lines as $index => $line) {
            if ($index === $cursorPos->y) {
                if ($line === "") {
                    $displayLines[] = Line::fromSpans(
                        Span::fromString("_")->style(Style::default()->bg(RgbColor::fromRgb(255, 255, 255))->fg(RgbColor::fromRgb(0, 0, 0))),
                    );
                } else {
                    $before = mb_substr($line, 0, $cursorPos->x);
                    $after = mb_substr($line, $cursorPos->x);
                    $cursorChar = $cursorPos->x < mb_strlen($line) ? mb_substr($line, $cursorPos->x, 1) : '_';
                    $displayLines[] = Line::fromSpans(
                        Span::fromString($before),
                        Span::fromString($cursorChar)->style(Style::default()->bg(RgbColor::fromRgb(255, 255, 255))->fg(RgbColor::fromRgb(0, 0, 0))),
                        Span::fromString($after),
                    );
                }
            } else {
                $displayLines[] = Line::fromString($line ?: " ");
            }
        }
        return BlockWidget::default()
            ->borders(Borders::ALL)
            ->titles(Title::fromString($this->label))
            ->widget(
                ParagraphWidget::fromText(
                    Text::fromLines(...$displayLines)
                )->wrap(Wrap::Word)
            );
    }


    public function handleKeybindAction(KeyboardAction $keyboardAction): void {
        $event = $keyboardAction->getEvent();

        if ($this->logger) {
            $this->logger->info($event->__toString());
        }

        if ($event instanceof CharKeyEvent) {
            $this->textEditor->insert($event->char);
        } elseif ($event instanceof CodedKeyEvent) {
            if ($event->code === KeyCode::Backspace) {
                $this->textEditor->deleteBackwards();
            } elseif ($event->code === KeyCode::Delete) {
                $this->textEditor->delete();
            } elseif ($event->code === KeyCode::Enter) {
                $this->textEditor->newLine();
            } elseif ($event->code === KeyCode::Left) {
                $this->textEditor->cursorLeft();
            } elseif ($event->code === KeyCode::Right) {
                $this->textEditor->cursorRight();
            } elseif ($event->code === KeyCode::Up) {
                $this->textEditor->cursorUp();
            } elseif ($event->code === KeyCode::Down) {
                $this->textEditor->cursorDown();
            }
        }
    }

}
