<?php

declare(strict_types=1);

namespace PhpTui\Tui\Extension\Core\Widget;

use PhpTui\Tui\Extension\Core\Widget\List\HighlightSpacing;
use PhpTui\Tui\Extension\Core\Widget\List\ListItem;
use PhpTui\Tui\Extension\Core\Widget\List\ListState;
use PhpTui\Tui\Style\Style;
use PhpTui\Tui\Widget\Corner;
use PhpTui\Tui\Widget\Widget;

/**
 * The List widget allows you to list and highlight items.
 */
final class ListWidget implements Widget
{
    /**
     * @param list<ListItem> $items
     */
    public function __construct(
        public array $items,
        public Style $style,
        public Corner $startCorner,
        public Style $highlightStyle,
        public string $highlightSymbol,
        public ListState $state,
        public HighlightSpacing $highlightSpacing,
    ) {
    }

    public static function default(): self
    {
        return new self(
            items: [],
            style: Style::default(),
            startCorner: Corner::TopLeft,
            highlightStyle: Style::default(),
            highlightSymbol: '>>',
            state: new ListState(0, null),
            highlightSpacing: HighlightSpacing::WhenSelected,
        );
    }

    public function items(ListItem ...$items): self
    {
        $this->items = array_values($items);

        return $this;
    }

    public function startCorner(Corner $corner): self
    {
        $this->startCorner = $corner;

        return $this;
    }

    public function select(int $selection): self
    {
        $this->state->selected = $selection;

        return $this;
    }

    public function offset(int $offset): self
    {
        $this->state->offset = $offset;

        return $this;
    }

    public function state(ListState $state): self
    {
        $this->state = $state;

        return $this;
    }

    public function highlightSymbol(string $symbol): self
    {
        $this->highlightSymbol = $symbol;

        return $this;
    }

    public function highlightStyle(Style $highlightStyle): self
    {
        $this->highlightStyle = $highlightStyle;

        return $this;
    }
}
