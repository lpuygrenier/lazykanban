<?php

namespace Lpuygrenier\Lazykanban\Service;

use Lpuygrenier\Lazykanban\Entity\Board;
use Monolog\Logger;

class BoardManager
{
    private FileService $fileService;
    private Logger $logger;
    private Board $currentBoard;
    private string $currentBoardFilename;

    public function __construct(FileService $fileService, Logger $logger, string $defaultFilename = 'board.json')
    {
        $this->fileService = $fileService;
        $this->logger = $logger;
        $this->currentBoardFilename = $defaultFilename;
        $this->currentBoard = $this->fileService->import($defaultFilename);
    }

    public function getCurrentBoard(): Board
    {
        return $this->currentBoard;
    }

    public function getCurrentBoardFilename(): string
    {
        return $this->currentBoardFilename;
    }

    public function switchToBoard(string $boardFile): void
    {
        $this->logger->info("Switching to board: {$boardFile}");
        try {
            $this->currentBoard = $this->fileService->import($boardFile);
            $this->currentBoardFilename = $boardFile;
        } catch (\Exception $e) {
            $this->logger->error("Failed to switch to board {$boardFile}: " . $e->getMessage());
            // Could throw or handle error
        }
    }

    public function saveCurrentBoard(): void
    {
        $this->logger->info("Saving board: {$this->currentBoardFilename}");
        try {
            $this->fileService->export($this->currentBoard, $this->currentBoardFilename);
        } catch (\Exception $e) {
            $this->logger->error("Failed to save board {$this->currentBoardFilename}: " . $e->getMessage());
        }
    }

    public function createBoard(string $name, ?Board $editingBoard = null): void
    {
        $baseFilename = $this->fileService->sanitizeFilename($name);
        $filename = $baseFilename . '.json';
        $counter = 1;
        $boardDir = $this->fileService->getBoardDirectory();
        while (file_exists($boardDir . '/' . $filename)) {
            $filename = $baseFilename . '_' . $counter . '.json';
            $counter++;
        }

        $this->logger->info("Creating board: {$filename}");

        if ($editingBoard !== null) {
            // Rename existing board
            $this->currentBoard = $editingBoard;
            $this->currentBoard->name = $name;
            $this->currentBoardFilename = $filename;
            $this->fileService->export($this->currentBoard, $filename);
        } else {
            // Create new board
            $this->currentBoard = new Board($name);
            $this->currentBoardFilename = $filename;
            $this->fileService->export($this->currentBoard, $filename);
        }
    }

    public function updateBoard(Board $newBoard): void
    {
        $this->currentBoard = $newBoard;
    }
}