<?php

namespace Lpuygrenier\Lazykanban\Gui\Page\State;

use Lpuygrenier\Lazykanban\Gui\Common\KeyboardAction;
use Lpuygrenier\Lazykanban\Gui\Page\KanbanPage;

interface KanbanPageState
{
    public function handleKeybindAction(KanbanPage $page, KeyboardAction $keyboardAction): ?KanbanPageState;
    public function getActiveComponent(KanbanPage $page): string;
    public function isEditingTask(KanbanPage $page): bool;
    public function isEditingBoard(KanbanPage $page): bool;
    public function isFiltering(KanbanPage $page): bool;
}