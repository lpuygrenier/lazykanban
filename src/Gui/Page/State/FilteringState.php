<?php

namespace Lpuygrenier\Lazykanban\Gui\Page\State;

use Lpuygrenier\Lazykanban\Gui\Common\KeyboardAction;
use Lpuygrenier\Lazykanban\Gui\Page\KanbanPage;

class FilteringState implements KanbanPageState
{
    public function handleKeybindAction(KanbanPage $page, KeyboardAction $keyboardAction): ?KanbanPageState
    {
        $page->getFilterInput()->setActive(true);
        $page->getFilterInput()->clear();
        $page->getFilterInput()->handleKeybindAction($keyboardAction);
        return null;
    }

    public function getActiveComponent(KanbanPage $page): string
    {
        return $page->getActiveComponent();
    }

    public function isEditingTask(KanbanPage $page): bool
    {
        return false;
    }

    public function isEditingBoard(KanbanPage $page): bool
    {
        return false;
    }

    public function isFiltering(KanbanPage $page): bool
    {
        return true;
    }
}