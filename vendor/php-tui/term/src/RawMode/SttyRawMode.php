<?php

declare(strict_types=1);

namespace PhpTui\Term\RawMode;

use PhpTui\Term\ProcessRunner;
use PhpTui\Term\ProcessRunner\ProcRunner;
use PhpTui\Term\RawMode;
use RuntimeException;

final class SttyRawMode implements RawMode
{
    private ?string $originalSettings = null;

    private function __construct(private readonly ProcessRunner $runner)
    {
    }

    public static function new(?ProcessRunner $processRunner = null): self
    {
        return new self($processRunner ?? new ProcRunner());
    }

    public function enable(): void
    {
        if (null !== $this->originalSettings) {
            return;
        }

        $result = $this->runner->run(['stty', '-g']);
        if ($result->exitCode !== 0) {
            throw new RuntimeException(
                'Could not get stty settings'
            );
        }

        $this->originalSettings = trim($result->stdout);

        $result = $this->runner->run(['stty', 'raw']);
        if ($result->exitCode !== 0) {
            throw new RuntimeException(
                'Could not set raw mode'
            );
        }
        $result = $this->runner->run(['stty', '-echo']);
        if ($result->exitCode !== 0) {
            throw new RuntimeException(
                'Could not disable echo'
            );
        }
    }

    public function disable(): void
    {
        if (null === $this->originalSettings) {
            return;
        }
        $result = $this->runner->run(['stty', $this->originalSettings]);
        if ($result->exitCode !== 0) {
            throw new RuntimeException(sprintf(
                'Could not restore from raw mode: %s',
                $result->stderr
            ));
        }
        $this->originalSettings = null;
    }

    public function isEnabled(): bool
    {
        return $this->originalSettings !== null;
    }
}
