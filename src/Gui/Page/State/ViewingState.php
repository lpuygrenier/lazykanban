<?php

namespace Lpuygrenier\Lazykanban\Gui\Page\State;

use Lpuygrenier\Lazykanban\Gui\Common\KeyboardAction;
use Lpuygrenier\Lazykanban\Gui\Page\KanbanPage;
use Lpuygrenier\Lazykanban\Constants\Keybinds;

class ViewingState implements KanbanPageState
{
    public function handleKeybindAction(KanbanPage $page, KeyboardAction $keyboardAction): ?KanbanPageState
    {
        $action = $keyboardAction->getAction();
        if ($action === null) {
            return null;
        }

        switch ($action) {
            case Keybinds::ACTION_CREATE_TASK:
                if ($page->getActiveComponent() === 'task') {
                    $page->getTaskForm()->setCreateMode();
                    return new EditingTaskState();
                } elseif ($page->getActiveComponent() === 'boardsection') {
                    $page->getBoardForm()->setCreateMode();
                    return new EditingBoardState();
                }
                break;
            case Keybinds::ACTION_SELECT:
                if ($page->getActiveComponent() === 'task') {
                    $selectedTask = $page->getSelectedTask();
                    if ($selectedTask !== null) {
                        $page->getTaskForm()->setEditMode($selectedTask);
                        return new EditingTaskState();
                    }
                }
                break;
            case Keybinds::ACTION_MOVE_LEFT:
                $page->setActiveComponent('task');
                break;
            case Keybinds::ACTION_MOVE_RIGHT:
                $page->setActiveComponent('boardsection');
                break;
            case Keybinds::ACTION_MOVE_UP:
            case Keybinds::ACTION_MOVE_DOWN:
                $page->getCurrentComponent()->handleKeybindAction($keyboardAction);
                break;
            case Keybinds::ACTION_MOVE_TASK:
            case Keybinds::ACTION_DELETE_TASK:
                if ($page->getActiveComponent() === 'task') {
                    $page->getTaskComponent()->handleKeybindAction($keyboardAction);
                }
                break;
            case Keybinds::ACTION_SEARCH:
                if (in_array($page->getActiveComponent(), ['task', 'boardsection'])) {
                    return new FilteringState();
                }
                break;
        }

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
        return false;
    }
}