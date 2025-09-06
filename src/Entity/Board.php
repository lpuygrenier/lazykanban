<?php

namespace Lpuygrenier\Lazykanban\Entity;

class Board {

    public int $id;
    public string $name;
    public array $todo = [];
    public array $inProgress = [];
    public array $done = [];

    public function __construct() {}

    public function add(Task $task): void {
        $this->todo[$task->getId()] = $task;
    }

    public function remove(Task $task): void {
        $id = $task->getId();

        if (isset($this->todo[$id])) {
            unset($this->todo[$id]);
        }
        if (isset($this->inProgress[$id])) {
            unset($this->inProgress[$id]);
        }
        if (isset($this->done[$id])) {
            unset($this->done[$id]);
        }
    }

    public function move(Task $task, Status $destination): void {
        $id = $task->getId();

        // Remove from any current list
        $this->remove($task);

        // Add to the destination list
        switch ($destination) {
            case Status::TODO:
                $this->todo[$id] = $task;
                break;
            case Status::IN_PROGRESS:
                $this->inProgress[$id] = $task;
                break;
            case Status::DONE:
                $this->done[$id] = $task;
                break;
        }
    }

    public function __toString(): string {
        $todoCount = count($this->todo);
        $inProgressCount = count($this->inProgress);
        $doneCount = count($this->done);
        $totalTasks = $todoCount + $inProgressCount + $doneCount;

        return sprintf(
            "Board #%d: %s (%d tasks total - TODO: %d, IN_PROGRESS: %d, DONE: %d)",
            $this->id,
            $this->name,
            $totalTasks,
            $todoCount,
            $inProgressCount,
            $doneCount
        );
    }
}
