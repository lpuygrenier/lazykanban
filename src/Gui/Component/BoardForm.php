<?php

declare(strict_types=1);

namespace Lpuygrenier\Lazykanban\Gui\Component;

use Lpuygrenier\Lazykanban\Gui\Constant\Widgets;
use Lpuygrenier\Lazykanban\Gui\KeyboardAction;
use Lpuygrenier\Lazykanban\Gui\Interfaces\IGuiComponent;
use PhpTui\Tui\Extension\Core\Widget\GridWidget;
use PhpTui\Tui\Extension\Core\Widget\ParagraphWidget;
use PhpTui\Tui\Layout\Constraint;
use PhpTui\Tui\Text\Text;
use PhpTui\Tui\Widget\Direction;
use PhpTui\Tui\Widget\Widget;
use PhpTui\Term\Event\CodedKeyEvent;
use PhpTui\Term\KeyCode;

final class BoardForm implements IGuiComponent
{
    private Input $nameInput;
    private string $activeField = 'name';
    private $onSubmit = null;
    private $onCancel = null;
    private bool $isEditMode = false;
    private $editingBoard = null;

    public function __construct()
    {
        $this->nameInput = new Input();
        $this->nameInput->setLabel('Board Name');
    }

    public function setOnSubmit(callable $callback): void
    {
        $this->onSubmit = $callback;
    }

    public function setOnCancel(callable $callback): void
    {
        $this->onCancel = $callback;
    }

    public function setEditMode($board): void
    {
        $this->isEditMode = true;
        $this->editingBoard = $board;
        $this->nameInput->setText($board->name);
        $this->activeField = 'name';
    }

    public function setCreateMode(): void
    {
        $this->isEditMode = false;
        $this->editingBoard = null;
        $this->clearInputs();
    }

    public function clearInputs(): void
    {
        $this->nameInput->clear();
        $this->activeField = 'name';
        // Reset active states
        $this->nameInput->setActive(true);
        // Reset edit mode
        $this->isEditMode = false;
        $this->editingBoard = null;
    }

    public function build(): Widget
    {
        // Set active state on input
        $this->nameInput->setActive($this->activeField === 'name');

        $content = GridWidget::default()
            ->direction(Direction::Vertical)
            ->constraints(
                Constraint::length(3)
            )
            ->widgets(
                $this->nameInput->build()
            );

        return GridWidget::default()
            ->direction(Direction::Vertical)
            ->constraints(
                Constraint::percentage(35), // top
                Constraint::percentage(30), // content
                Constraint::percentage(35)  // bottom
            )
            ->widgets(
                Widgets::$EMPTY,
                GridWidget::default()
                    ->direction(Direction::Horizontal)
                    ->constraints(
                        Constraint::percentage(25), // left
                        Constraint::percentage(50), // content
                        Constraint::percentage(25)  // right
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

        // Handle Enter - submit
        if ($event instanceof CodedKeyEvent && $event->code === KeyCode::Enter) {
            if ($this->activeField === 'name' && $this->onSubmit) {
                $name = $this->nameInput->getText();
                ($this->onSubmit)($name, $this->editingBoard);
                $this->clearInputs();
                return;
            }
        }

        // Handle Escape to cancel
        if ($event instanceof CodedKeyEvent && $event->code === KeyCode::Esc) {
            if ($this->onCancel) {
                ($this->onCancel)();
                $this->clearInputs();
            }
            return;
        }

        // Pass other events to the input
        if ($this->activeField === 'name') {
            $this->nameInput->handleKeybindAction($keyboardAction);
        }
    }

    public function getKeybindActions(): array
    {
        return [];
    }
}