<?php

declare(strict_types=1);

namespace Lpuygrenier\Lazykanban\Gui\Interfaces;


interface IFilterable
{
    public function setFilter(?callable $filter): void;
    public function clearFilter(): void;
    public function getFilteredItems(): array;
}
