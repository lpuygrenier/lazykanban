<?php

namespace Lpuygrenier\Lazykanban\Service;

use Monolog\Logger;

class ConfigService {
    private array $config = [];
    private Logger $logger;

    public function __construct(string $configFilePath, Logger $logger) {
        $this->logger = $logger;
        $this->loadConfig($configFilePath);
    }

    private function loadConfig(string $configFilePath): void {
        $this->logger->info('[ConfigService] - Loading config from: ' . $configFilePath);

        if (!file_exists($configFilePath)) {
            $this->logger->warning('[ConfigService] - Config file does not exist: ' . $configFilePath);
            return;
        }

        $json = file_get_contents($configFilePath);
        $data = json_decode($json, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            $this->logger->error('[ConfigService] - Invalid JSON in config file: ' . json_last_error_msg());
            return;
        }

        $this->config = $data;
        $this->logger->info('[ConfigService] - Config loaded successfully');
    }

    public function get(string $key, $default = null) {
        return $this->config[$key] ?? $default;
    }

    public function getAll(): array {
        return $this->config;
    }

    public function has(string $key): bool {
        return isset($this->config[$key]);
    }

    public function getBoardSyncDirectory(): string {
        return $this->get('boardSyncDirectory', './data/board');
    }
}