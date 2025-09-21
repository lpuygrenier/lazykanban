<?php

namespace Lpuygrenier\Lazykanban\Constants;

class Keybinds
{
    const ACTION_QUIT = 'quit';
    const ACTION_HELP = 'help';
    const ACTION_MOVE_UP = 'move_up';
    const ACTION_MOVE_DOWN = 'move_down';
    const ACTION_MOVE_LEFT = 'move_left';
    const ACTION_MOVE_RIGHT = 'move_right';
    const ACTION_SELECT = 'select';
    const ACTION_CREATE_TASK = 'create_task';
    const ACTION_MOVE_TASK = 'move_task';
    const ACTION_DELETE_TASK = 'delete_task';
    const ACTION_SEARCH = 'search';

    public static function getDescriptions(): array
    {
        return [
            self::ACTION_QUIT => 'Quit the application',
            self::ACTION_HELP => 'Show help',
            self::ACTION_MOVE_UP => 'Move cursor up',
            self::ACTION_MOVE_DOWN => 'Move cursor down',
            self::ACTION_MOVE_LEFT => 'Move cursor left',
            self::ACTION_MOVE_RIGHT => 'Move cursor right',
            self::ACTION_SELECT => 'Select current item',
            self::ACTION_CREATE_TASK => 'Create a new task',
            self::ACTION_MOVE_TASK => 'Move task to next status',
            self::ACTION_DELETE_TASK => 'Delete selected task',
            self::ACTION_SEARCH => 'Toggle search mode',
        ];
    }

    public static function getActionToKey(): array
    {
        return [
            self::ACTION_QUIT => 'q',
            self::ACTION_HELP => '?',
            self::ACTION_MOVE_UP => 'k',
            self::ACTION_MOVE_DOWN => 'j',
            self::ACTION_MOVE_LEFT => 'h',
            self::ACTION_MOVE_RIGHT => 'l',
            self::ACTION_SELECT => 'enter',
            self::ACTION_CREATE_TASK => 'n',
            self::ACTION_MOVE_TASK => 'm',
            self::ACTION_DELETE_TASK => 'd',
            self::ACTION_SEARCH => '/',
        ];
    }

    public static function getDefaultKeybinds(): array
    {
        return self::getActionToKey();
    }
}