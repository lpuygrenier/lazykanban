<?php
declare(strict_types=1);

namespace Lpuygrenier\Lazykanban\Gui\Component;


use Lpuygrenier\Lazykanban\Gui\Common\IGuiComponent;
use Lpuygrenier\Lazykanban\Gui\KeyboardAction;
use PhpTui\Tui\Extension\Core\Widget\GridWidget;
use PhpTui\Tui\Extension\Core\Widget\ParagraphWidget;
use PhpTui\Tui\Layout\Constraint;
use PhpTui\Tui\Widget\Direction;
use PhpTui\Tui\Widget\HorizontalAlignment;
use PhpTui\Tui\Widget\Widget;

final class StatusBar implements IGuiComponent {

    private ParagraphWidget $appVersionTxt;

    public function __construct() {
        $this->appVersionTxt= ParagraphWidget::fromString(APP_VERS)->alignment(HorizontalAlignment::Right);
    }

    public function build(): Widget {
        return GridWidget::default()
            ->direction(Direction::Horizontal)
            ->constraints(
                Constraint::percentage(50),
                Constraint::percentage(50),
            )
            ->widgets(
                ParagraphWidget::fromString("HelloWorld!"),
                $this->appVersionTxt
            );

    }

    public function handleKeybindAction(KeyboardAction $keyboardAction): void {

    }

    public function getKeybindActions(): array {
        return [];
    }
}
