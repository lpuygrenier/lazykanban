<?php

declare(strict_types=1);

namespace PhpTui\BDF;

final class BdfProperties
{
    /**
     * @param array<string,string|int> $properties
     */
    public function __construct(public readonly array $properties = [])
    {
    }

    public function get(BdfProperty $property): null|int|string
    {
        return $this->properties[$property->name] ?? null;
    }
}
