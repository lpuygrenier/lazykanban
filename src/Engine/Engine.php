<?php
namespace Lpuygrenier\Lazykanban\Engine;

use Lpuygrenier\Lazykanban\Gui\GuiComponent;
use Lpuygrenier\Lazykanban\Service\FileService;
use Lpuygrenier\Lazykanban\Service\ConfigService;
use Lpuygrenier\Lazykanban\Service\KeybindService;
use Lpuygrenier\Lazykanban\Gui\Page\KanbanPage;
use Monolog\Logger;
use Lpuygrenier\Lazykanban\Entity\Board;
use PhpTui\Term\Actions;
use PhpTui\Term\ClearType;
use PhpTui\Term\Event\CharKeyEvent;
use PhpTui\Term\Event\CodedKeyEvent;
use PhpTui\Term\KeyCode;
use PhpTui\Term\KeyModifiers;
use PhpTui\Term\Terminal;
use PhpTui\Tui\Bridge\PhpTerm\PhpTermBackend as PhpTuiPhpTermBackend;
use PhpTui\Tui\Display\Backend;
use PhpTui\Tui\Display\Display;
use PhpTui\Tui\DisplayBuilder;
use PhpTui\Tui\Extension\Bdf\BdfExtension;
use PhpTui\Tui\Extension\Core\Widget\GridWidget;
use PhpTui\Tui\Extension\ImageMagick\ImageMagickExtension;
use PhpTui\Tui\Layout\Constraint;
use PhpTui\Tui\Widget\Direction;
use PhpTui\Tui\Widget\Widget;
use Throwable;

class Engine {

    private Logger $logger;
    private FileService $fileService;
    private ConfigService $configService;
    private KeybindService $keybindService;
    private Terminal $terminal;
    private Display $display;
    private GuiComponent $currentGuiComponent;
    public Board $board;

    public function __construct(
        Logger $logger,
        FileService $fileService,
        ConfigService $configService,
        KeybindService $keybindService,
        string $defaultFilename = 'board.json'
    ) {
        $this->logger = $logger;
        $this->fileService = $fileService;
        $this->configService = $configService;
        $this->keybindService = $keybindService;

        // Load initial board
        $this->board = $this->fileService->import($defaultFilename);
    }

    public function initialize(Terminal $terminal, ?Backend $backend = null): void {
        $this->terminal = $terminal;

        // Setup display
        $this->display = DisplayBuilder::default($backend ?? PhpTuiPhpTermBackend::new($terminal))
            ->addExtension(new ImageMagickExtension())
            ->addExtension(new BdfExtension())
            ->build();

        $this->currentGuiComponent = new KanbanPage();
    }

    public function run(): int {
        try {
            // Enable "raw" mode to remove default terminal behavior
            $this->terminal->execute(Actions::cursorHide());
            $this->terminal->execute(Actions::alternateScreenEnable());
            $this->terminal->execute(Actions::enableMouseCapture());
            $this->terminal->enableRawMode();

            return $this->doRun();
        } catch (Throwable $err) {
            $this->terminal->disableRawMode();
            $this->terminal->execute(Actions::disableMouseCapture());
            $this->terminal->execute(Actions::alternateScreenDisable());
            $this->terminal->execute(Actions::cursorShow());
            $this->terminal->execute(Actions::clear(ClearType::All));

            throw $err;
        }
    }

    private function doRun(): int {
        // Main application loop
        while (true) {
            // Handle events sent to the terminal
            while (null !== $event = $this->terminal->events()->next()) {
                if ($event instanceof CharKeyEvent) {
                    if ($event->modifiers === KeyModifiers::NONE) {
                        // Check for quit keybind
                        if ($this->keybindService->isActionKey($event->char, 'quit')) {
                            $this->logger->info('Quit key pressed, exiting application');
                            break 2;
                        }

                        $action = $this->keybindService->getActionForKey($event->char);
                        if ($action) {
                            $this->currentGuiComponent->handleKeybindAction($action);
                        }
                    }
                }
            }

            $this->display->draw($this->buildLayout($this->currentGuiComponent));
        }

        $this->terminal->disableRawMode();
        $this->terminal->execute(Actions::cursorShow());
        $this->terminal->execute(Actions::alternateScreenDisable());
        $this->terminal->execute(Actions::disableMouseCapture());

        return 0;
    }

    private function buildLayout(GuiComponent $guiComponent): Widget {
        return GridWidget::default()
            ->direction(Direction::Vertical)
            ->constraints(
                Constraint::percentage(100),
            )
            ->widgets(
                $guiComponent->build(),
            );
    }

    private function exportBoard(string $boardName): void {
        $this->logger->info("Saving board $boardName");
        $this->fileService->export($this->board, $boardName);
    }
    private function handleKeybindAction(string $action): void {
        switch ($action) {
            case 'save':
                $this->exportBoard('autosave.json');
                break;
            default:
                $this->logger->info("Unhandled keybind action: {$action}");
                break;
        }
    }
}