<?php

namespace Lpuygrenier\Lazykanban\Service;

use Lpuygrenier\Lazykanban\Entity\Board;
use Lpuygrenier\Lazykanban\Entity\Task;
use Lpuygrenier\Lazykanban\Service\ConfigService;
use Monolog\Logger;

class FileService {
    private ConfigService $configService;
    private Logger $logger;

    public function __construct(ConfigService $configService, Logger $logger) {
        $this->configService = $configService;
        $this->logger = $logger;
    }

    // Export the Board object to a JSON file
    public function export(Board $board, string $filename): void {
        $filePath = $this->buildFilePath($filename);

        $data = [
            'id' => $board->id,
            'name' => $board->name,
            'todo' => array_map(fn(Task $task) => $this->taskToArray($task), $board->todo),
            'inProgress' => array_map(fn(Task $task) => $this->taskToArray($task), $board->inProgress),
            'done' => array_map(fn(Task $task) => $this->taskToArray($task), $board->done),
        ];

        // Ensure directory exists
        $directory = dirname($filePath);
        if (!is_dir($directory)) {
            mkdir($directory, 0755, true);
        }

        file_put_contents($filePath, json_encode($data, JSON_PRETTY_PRINT));
        $this->logger->info('[FileService] - Exported board to: ' . $filePath);
    }

    // Import the Board object from a JSON file
    public function import(string $filename): Board {
        $this->logger->info('[FileService] - Start import');
        $board = new Board();

        $filePath = $this->buildFilePath($filename);
        $this->logger->info('[FileService] - Loading file: ' . $filePath);

        if (!file_exists($filePath)) {
            $this->logger->error('[FileService] - File does not exist: ' . $filePath);
            return $board; // Return empty board if file doesn't exist
        }

        $json = file_get_contents($filePath);
        $data = json_decode($json, true);

        if (!is_array($data)) {
            $this->logger->error('[FileService] - $data is not an array');
            return $board; // Return empty board if JSON invalid
        }

        // Set board properties
        $board->id = $data['id'] ?? 1;
        $board->name = $data['name'] ?? 'Default Board';

        // Load tasks as associative arrays with task IDs as keys
        $board->todo = $this->arrayToTasks($data['todo'] ?? []);
        $board->inProgress = $this->arrayToTasks($data['inProgress'] ?? []);
        $board->done = $this->arrayToTasks($data['done'] ?? []);

        $this->logger->info('[FileService] - End import');
        $this->logger->info($board);

        return $board;
    }

    private function taskToArray(Task $task): array {
        return [
            'id' => $task->getId(),
            'name' => $task->getName(),
            'description' => $task->getDescription(),
        ];
    }

    private function arrayToTasks(array $tasksArray): array {
        $tasks = [];
        foreach ($tasksArray as $taskData) {
            $task = new Task($taskData['id'], $taskData['name'], $taskData['description'] ?? '');
            $tasks[$task->getId()] = $task;
        }
        return $tasks;
    }

    public function listBoardFiles(): array {
        $syncDirectory = $this->configService->getBoardSyncDirectory();
        if (!is_dir($syncDirectory)) {
            $this->logger->warning('[FileService] - Board sync directory does not exist: ' . $syncDirectory);
            return [];
        }

        $files = scandir($syncDirectory);
        if ($files === false) {
            $this->logger->error('[FileService] - Failed to scan directory: ' . $syncDirectory);
            return [];
        }

        // Filter out . and .. and only include .json files
        $boardFiles = array_filter($files, function($file) {
            return $file !== '.' && $file !== '..' && pathinfo($file, PATHINFO_EXTENSION) === 'json';
        });

        $this->logger->info('[FileService] - Found ' . count($boardFiles) . ' board files');
        return array_values($boardFiles);
    }

    private function buildFilePath(string $filename): string {
        $syncDirectory = $this->configService->getBoardSyncDirectory();
        return rtrim($syncDirectory, '/') . '/' . ltrim($filename, '/');
    }
}