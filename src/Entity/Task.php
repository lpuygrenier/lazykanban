<?php

namespace Lpuygrenier\Lazykanban\Entity;

class Task {
    private int $id;
    private string $name;
    private string $description;

    public function __construct(int $id, string $name, string $description = '') {
        $this->id = $id;
        $this->name = $name;
        $this->description = $description;
    }

    public function getId(): int {
        return $this->id;
    }

    public function getName(): string {
        return $this->name;
    }

    public function getDescription(): string {
        return $this->description;
    }

    public function setName(string $name): void {
        $this->name = $name;
    }

    public function setDescription(string $description): void {
        $this->description = $description;
    }

    public function __toString(): string {
        $description = $this->description ? " - {$this->description}" : "";
        return sprintf("Task #%d: %s%s", $this->id, $this->name, $description);
    }
}