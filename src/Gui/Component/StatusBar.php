<?php
declare(strict_types=1);

namespace Lpuygrenier\Lazykanban\Gui\Component;


use Lpuygrenier\Lazykanban\Constants\Keybinds;
use Lpuygrenier\Lazykanban\Gui\Common\IGuiComponent;
use Lpuygrenier\Lazykanban\Gui\Common\KeyboardAction;
use Lpuygrenier\Lazykanban\Gui\Constant\Colors;
use PhpTui\Tui\Extension\Core\Widget\GridWidget;
use PhpTui\Tui\Extension\Core\Widget\ParagraphWidget;
use PhpTui\Tui\Layout\Constraint;
use PhpTui\Tui\Style\Style;
use PhpTui\Tui\Widget\Direction;
use PhpTui\Tui\Widget\HorizontalAlignment;
use PhpTui\Tui\Widget\Widget;

final class StatusBar implements IGuiComponent {

    private ParagraphWidget $appVersionTxt;
    private string $keybindsText;

    public function __construct() {
        $this->appVersionTxt= ParagraphWidget::fromString(APP_VERS)->alignment(HorizontalAlignment::Right);
    }

    public function setKeybinds(array $keybinds): void
    {
        $actionToKey = Keybinds::getActionToKey();
        $formatted = [];
        foreach ($keybinds as $keyboardAction) {
            if ($keyboardAction instanceof KeyboardAction && $keyboardAction->hasAction()) {
                $action = $keyboardAction->getAction();
                $desc = $keyboardAction->getDescription() ?? Keybinds::getDescriptions()[$action] ?? 'Unknown';
                $key = $actionToKey[$action] ?? '?';
                $formatted[] = $key . ': ' . $desc;
            }
        }
        $this->keybindsText = implode(' | ', $formatted);
    }

    public function build(): Widget {
        return GridWidget::default()
            ->direction(Direction::Horizontal)
            ->constraints(
                Constraint::percentage(50),
                Constraint::percentage(50),
            )
            ->widgets(
                ParagraphWidget::fromString($this->keybindsText)
                ->style(Style::default()->fg(Colors::$BLUE)),
                $this->appVersionTxt
            );

    }

    public function handleKeybindAction(KeyboardAction $keyboardAction): void {

    }

    public function getKeybindActions(): array {
        return [];
    }
}
