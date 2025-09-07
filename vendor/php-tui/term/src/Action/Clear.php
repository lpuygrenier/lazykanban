<?php

declare(strict_types=1);

namespace PhpTui\Term\Action;

use PhpTui\Term\Action;
use PhpTui\Term\ClearType;

final class Clear implements Action
{
    public function __construct(public readonly ClearType $clearType)
    {
    }
    public function __toString(): string
    {
        return sprintf('Clear(%s)', $this->clearType->name);
    }
}
