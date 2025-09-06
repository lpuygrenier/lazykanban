<?php

namespace Lpuygrenier\Lazykanban\Service;

use Monolog\Logger;

class KeybindService {
    private array $keybinds = [];
    private Logger $logger;

    public function __construct(string $keybindFilePath, Logger $logger) {
        $this->logger = $logger;
        $this->loadKeybinds($keybindFilePath);
    }

    private function loadKeybinds(string $keybindFilePath): void {
        $this->logger->info('[KeybindService] - Loading keybinds from: ' . $keybindFilePath);

        if (!file_exists($keybindFilePath)) {
            $this->logger->warning('[KeybindService] - Keybind file does not exist: ' . $keybindFilePath);
            $this->setDefaultKeybinds();
            return;
        }

        $json = file_get_contents($keybindFilePath);
        $data = json_decode($json, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            $this->logger->error('[KeybindService] - Invalid JSON in keybind file: ' . json_last_error_msg());
            $this->setDefaultKeybinds();
            return;
        }

        $this->keybinds = $data;
        $this->logger->info('[KeybindService] - Keybinds loaded successfully');
    }

    private function setDefaultKeybinds(): void {
        $this->keybinds = [
            'quit' => 'q',
            'help' => 'h',
            'save' => 's',
            'load' => 'l',
            'new_task' => 'n',
            'edit_task' => 'e',
            'delete_task' => 'd'
        ];
        $this->logger->info('[KeybindService] - Using default keybinds');
    }

    public function getKey(string $action): ?string {
        return $this->keybinds[$action] ?? null;
    }

    public function getAllKeybinds(): array {
        return $this->keybinds;
    }

    public function isActionKey(string $pressedKey, string $action): bool {
        $actionKey = $this->getKey($action);
        return $actionKey !== null && $pressedKey === $actionKey;
    }

    public function getActionForKey(string $pressedKey): ?string {
        foreach ($this->keybinds as $action => $key) {
            if ($key === $pressedKey) {
                return $action;
            }
        }
        return null;
    }
}