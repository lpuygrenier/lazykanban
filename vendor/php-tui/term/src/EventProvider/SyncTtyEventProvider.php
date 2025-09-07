<?php

declare(strict_types=1);

namespace PhpTui\Term\EventProvider;

use PhpTui\Term\Event;
use PhpTui\Term\EventParser;
use PhpTui\Term\EventProvider;
use PhpTui\Term\Reader;
use PhpTui\Term\Reader\StreamReader;

final class SyncTtyEventProvider implements EventProvider
{
    /**
     * @var Event[]
     */
    private array $buffer = [];

    public function __construct(private readonly Reader $reader, private readonly EventParser $parser)
    {
    }

    public static function new(): self
    {
        return new self(StreamReader::tty(), EventParser::new());
    }

    public function next(): ?Event
    {
        while ($event = array_shift($this->buffer)) {
            return $event;
        }
        while (null !== $line = $this->reader->read()) {
            // TODO: pass true here if we read as much as we could as there
            // _could_ still be more in this case.
            $this->parser->advance($line, more: false);
        }
        foreach ($this->parser->drain() as $event) {
            $this->buffer[] = $event;
        }
        while ($event = array_shift($this->buffer)) {
            return $event;
        }

        return null;
    }
}
