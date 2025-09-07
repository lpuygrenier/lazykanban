<?php

declare(strict_types=1);

namespace PhpTui\Tui\Extension\Core\Widget;

use PhpTui\Tui\Display\Area;
use PhpTui\Tui\Display\Buffer;
use PhpTui\Tui\Extension\Core\Widget\Table\TableCell;
use PhpTui\Tui\Layout\Constraint;
use PhpTui\Tui\Layout\Layout;
use PhpTui\Tui\Position\Position;
use PhpTui\Tui\Widget\Direction;
use PhpTui\Tui\Widget\HorizontalAlignment;
use PhpTui\Tui\Widget\Widget;
use PhpTui\Tui\Widget\WidgetRenderer;

final class TableRenderer implements WidgetRenderer
{
    public function render(
        WidgetRenderer $renderer,
        Widget $widget,
        Buffer $buffer,
        Area $area,
    ): void {
        if (!$widget instanceof TableWidget) {
            return;
        }
        $buffer->setStyle($area, $widget->style);

        $tableArea = $area;
        $selectionWidth = $widget->highlightSpacing->shouldAdd(
            $widget->state->selected !== null
        ) ? mb_strlen($widget->highlightSymbol) : 0;

        $columnWidths = $this->getColumnsWidths($widget, $tableArea->width, $selectionWidth);
        $highlightSymbol = $widget->highlightSymbol;
        $currentHeight = 0;
        $rowsHeight = $tableArea->height;

        $header = $widget->header;
        if ($header !== null) {
            $maxHeaderHight = min($tableArea->height, $header->totalHeight());
            $buffer->setStyle(
                Area::fromScalars(
                    $tableArea->left(),
                    $tableArea->top(),
                    $tableArea->width,
                    min($tableArea->height, $header->height)
                ),
                $header->style,
            );
            $innerOffset = $tableArea->left();
            foreach ($columnWidths as $i => $width) {
                $cell = $header->getCell($i);
                if (null === $cell) {
                    continue;
                }
                $this->renderCell($buffer, $cell, Area::fromScalars(
                    $innerOffset + $width[0],
                    $tableArea->top(),
                    $width[1],
                    $maxHeaderHight
                ));
            }
            $currentHeight += $maxHeaderHight;
            $rowsHeight = max(0, $rowsHeight - $maxHeaderHight);
        }
        if ($widget->rows === []) {
            return;
        }

        [$start, $end] = self::getRowBounds($widget, $rowsHeight);
        $widget->state->offset = $start;
        foreach (array_slice($widget->rows, $start, $end - $start) as $rowIndex => $tableRow) {
            [$rowY, $innerOffset] = [$tableArea->top() + $currentHeight, $tableArea->left()];

            $currentHeight += $tableRow->totalHeight();
            $tableRowArea = Area::fromScalars($innerOffset, $rowY, $tableArea->width, $tableRow->height);
            $buffer->setStyle($tableRowArea, $tableRow->style);
            $isSelected = $widget->state->selected === $rowIndex + $start;
            if ($selectionWidth > 0 && $isSelected) {
                $buffer->putString(
                    Position::at(
                        $innerOffset,
                        $rowY,
                    ),
                    $highlightSymbol,
                    $tableRow->style,
                    $tableArea->width,
                );
            }
            foreach ($columnWidths as $cellIndex => $width) {
                $cell = $tableRow->getCell($cellIndex);
                if (null === $cell) {
                    continue;
                }
                $this->renderCell($buffer, $cell, Area::fromScalars(
                    $innerOffset + $width[0],
                    $rowY,
                    $width[1],
                    $tableRow->height,
                ));
            }
            if ($isSelected) {
                $buffer->setStyle($tableRowArea, $widget->highlightStyle);
            }
        }
    }

    /**
     * @param int<0,max> $maxWidth
     * @param int<0,max> $selectionWidth
     * @return list<array{int<0,max>,int<0,max>}>
     */
    private function getColumnsWidths(TableWidget $table, int $maxWidth, int $selectionWidth): array
    {
        $constraints = [
            Constraint::length($selectionWidth)
        ];
        foreach ($table->widths as $constraint) {
            $constraints[] = $constraint;
            $constraints[] = Constraint::length($table->columnSpacing);
        }
        if ([] !== $table->widths) {
            array_pop($constraints);
        }
        $chunks = Layout::default()
            ->direction(Direction::Horizontal)
            ->constraints($constraints)
            ->split(Area::fromDimensions($maxWidth, 1));

        $widths = [];
        // iterate over the specified width constraints
        $counter = count($constraints);

        // iterate over the specified width constraints
        for ($i = 1; $i < $counter; $i += 2) {
            $chunk = $chunks->get($i);
            $widths[] = [$chunk->position->x, $chunk->width];
        }

        return $widths;
    }

    private function renderCell(Buffer $buffer, TableCell $cell, Area $area): void
    {
        $buffer->setStyle($area, $cell->style);
        /** @var int<0,max> $i */
        foreach ($cell->content->lines as $i => $line) {
            if ($i >= $area->height) {
                break;
            }

            $xOffset = match ($line->alignment) {
                HorizontalAlignment::Center => max(0, (int) (($area->width / 2) - max(0, ($line->width() / 2)))),
                HorizontalAlignment::Right => max($area->width - 0, $line->width()),
                default => 0,
            };
            $buffer->putLine(
                Position::at(
                    $area->position->x + $xOffset,
                    $area->position->y + $i
                ),
                $line,
                $area->width
            );
        }
    }

    /**
     * TODO: This is the same as ItemList except for row/item which can
     *       be refactored to implement an interface.
     *
     * @return array{int,int}
     */
    private function getRowBounds(TableWidget $table, int $maxHeight): array
    {
        $offset = min($table->state->offset, max(count($table->rows) - 1, 0));
        $start = $end = $offset;
        $height = 0;
        foreach (array_slice($table->rows, $start) as $row) {
            if ($height + $row->height > $maxHeight) {
                break;
            }
            $height += $row->height;
            $end += 1;
        }

        if ($table->state->selected !== null) {
            $selected = min(count($table->rows) - 1, $table->state->selected ?? 0);
            while ($selected >= $end) {
                $height = $height += $table->rows[$end]->height;
                $end += 1;
                while ($height > $maxHeight) {
                    $height = max(0, $height - $table->rows[$start]->height);
                    $start += 1;
                }
            }
            while ($selected < $start) {
                $start -= 1;
                $height = $height += $table->rows[$start]->height;
                while ($height > $maxHeight) {
                    $end -= 1;
                    $height = max(0, $height - $table->rows[$end]->height);
                }
            }
        }

        return [$start, $end];
    }
}
