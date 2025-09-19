<?php

declare(strict_types=1);

namespace Lpuygrenier\Lazykanban\Gui\Component;

use Lpuygrenier\Lazykanban\Gui\KeyboardAction;
use Lpuygrenier\Lazykanban\Gui\GuiComponent;
use PhpTui\Tui\Extension\Core\Widget\GridWidget;
use PhpTui\Tui\Layout\Constraint;
use PhpTui\Tui\Widget\Direction;
use PhpTui\Tui\Widget\Widget;
use PhpTui\Term\Event\CodedKeyEvent;
use PhpTui\Term\KeyCode;

final class CreateTaskForm implements GuiComponent
{
    private Input $nameInput;
    private Input $descriptionInput;
    private string $activeField = 'name';
    private $onSubmit = null;
    private $onCancel = null;

    public function __construct()
    {
        $this->nameInput = new Input();
        $this->nameInput->setLabel('Task Name');
        $this->descriptionInput = new Input();
        $this->descriptionInput->setLabel('Description');
    }

    public function setOnSubmit(callable $callback): void
    {
        $this->onSubmit = $callback;
    }

    public function setOnCancel(callable $callback): void
    {
        $this->onCancel = $callback;
    }

    public function clearInputs(): void
    {
        $this->nameInput->clear();
        $this->descriptionInput->clear();
        $this->activeField = 'name';
        // Reset active states
        $this->nameInput->setActive(true);
        $this->descriptionInput->setActive(false);
    }

    public function build(): Widget
    {
        // Set active state on inputs
        $this->nameInput->setActive($this->activeField === 'name');
        $this->descriptionInput->setActive($this->activeField === 'description');

        return GridWidget::default()
            ->direction(Direction::Vertical)
            ->constraints(
                Constraint::length(3),
                Constraint::length(5)
            )
            ->widgets(
                $this->nameInput->build(),
                $this->descriptionInput->build()
            );
    }

    public function handleKeybindAction(KeyboardAction $keyboardAction): void
    {
        $event = $keyboardAction->getEvent();

        // Handle Tab to switch between fields
        if ($event instanceof CodedKeyEvent && $event->code === KeyCode::Tab) {
            $this->activeField = $this->activeField === 'name' ? 'description' : 'name';
            return;
        }

        // Handle Enter - submit only when name field is active
        if ($event instanceof CodedKeyEvent && $event->code === KeyCode::Enter) {
            if ($this->activeField === 'name' && $this->onSubmit) {
                $name = $this->nameInput->getText();
                $description = $this->descriptionInput->getText();
                ($this->onSubmit)($name, $description);
                $this->clearInputs();
                return;
            }
            // If description field is active, let Enter pass through to create new line
        }

        // Handle Escape to cancel
        if ($event instanceof CodedKeyEvent && $event->code === KeyCode::Esc) {
            if ($this->onCancel) {
                ($this->onCancel)();
                $this->clearInputs();
            }
            return;
        }

        // Pass other events to active input
        if ($this->activeField === 'name') {
            $this->nameInput->handleKeybindAction($keyboardAction);
        } else {
            $this->descriptionInput->handleKeybindAction($keyboardAction);
        }
    }
}