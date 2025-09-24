<?php
namespace Lpuygrenier\Lazykanban\Engine;

use Lpuygrenier\Lazykanban\Gui\Component\HelpComponent;
use Lpuygrenier\Lazykanban\Gui\Component\Input;
use Lpuygrenier\Lazykanban\Gui\Component\StatusBar;
use Lpuygrenier\Lazykanban\Gui\Common\IGuiComponent;
use Lpuygrenier\Lazykanban\Gui\Common\KeyboardAction;
use Lpuygrenier\Lazykanban\Constants\Keybinds;
use Lpuygrenier\Lazykanban\Gui\Component\TooSmallScreenSizeComponent;
use Lpuygrenier\Lazykanban\Gui\Constant\Widgets;
use Lpuygrenier\Lazykanban\Service\FileService;
use Lpuygrenier\Lazykanban\Service\ConfigService;
use Lpuygrenier\Lazykanban\Service\KeybindService;
use Lpuygrenier\Lazykanban\Service\BoardManager;
use Lpuygrenier\Lazykanban\Gui\Page\KanbanPage;
use Monolog\Logger;
use Lpuygrenier\Lazykanban\Entity\Board;
use PhpTui\Term\Actions;
use PhpTui\Term\ClearType;
use PhpTui\Term\Event;
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
    private BoardManager $boardManager;
    private Terminal $terminal;
    private Display $display;
    private IGuiComponent $currentIGuiComponent;
    private ?IGuiComponent $previousIGuiComponent = null;
    private StatusBar $statusBar;

    public function __construct(
        Logger $logger,
        FileService $fileService,
        ConfigService $configService,
        KeybindService $keybindService,
        BoardManager $boardManager
    ) {
        $this->statusBar = new StatusBar();

        $this->logger = $logger;
        $this->fileService = $fileService;
        $this->configService = $configService;
        $this->keybindService = $keybindService;
        $this->boardManager = $boardManager;
    }

    public function initialize(Terminal $terminal, ?Backend $backend = null): void {
        $this->terminal = $terminal;

        // Setup display
        $this->display = DisplayBuilder::default($backend ?? PhpTuiPhpTermBackend::new($terminal))
            ->addExtension(new ImageMagickExtension())
            ->addExtension(new BdfExtension())
            ->build();

        $boardFiles = $this->fileService->listBoardFiles();
        $this->currentIGuiComponent = new KanbanPage($this->boardManager->getCurrentBoard(), $boardFiles);

        // Set up board switching callback
        if ($this->currentIGuiComponent instanceof KanbanPage) {
            $this->currentIGuiComponent->setOnBoardSwitch(function(string $boardFile) {
                $this->boardManager->switchToBoard($boardFile);
                $this->currentIGuiComponent->updateBoard($this->boardManager->getCurrentBoard());
            });

            // Set up board save callback
            $this->currentIGuiComponent->setOnBoardSave(function() {
                $this->boardManager->saveCurrentBoard();
            });

            // Set up board create callback
            $this->currentIGuiComponent->setOnBoardCreate(function(string $name, $editingBoard = null) {
                $this->boardManager->createBoard($name, $editingBoard);
                $this->currentIGuiComponent->updateBoard($this->boardManager->getCurrentBoard());
                $boardFiles = $this->fileService->listBoardFiles();
                $this->currentIGuiComponent->updateBoardFiles($boardFiles, count($boardFiles) - 1);
            });
        }

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
                if ($this->isQuitEvent($event)) {
                    $this->logger->info('Quit key pressed, exiting application');
                    break 2;
                }

                $keyboardAction = $this->getKeyboardActionFromEvent($event);
                if ($this->isGlobalAction($keyboardAction)) {
                    $this->handleGlobalKeybindAction($keyboardAction);
                } else {
                    $this->currentIGuiComponent->handleKeybindAction($keyboardAction);
                }
            }

            /** Render the app */
            try {
                $this->display->draw($this->buildLayout($this->currentIGuiComponent));
            } catch (Throwable $err) {
                $errorComponent = new TooSmallScreenSizeComponent();
                $this->display->draw($errorComponent->build());
            }


            // sleep for Xms - note that it's encouraged to implement apps
            // using an async library such as Amp or React
            usleep(50_000);
        }

        $this->terminal->disableRawMode();
        $this->terminal->execute(Actions::cursorShow());
        $this->terminal->execute(Actions::alternateScreenDisable());
        $this->terminal->execute(Actions::disableMouseCapture());

        return 0;
    }

    private function isQuitEvent(Event $event): bool
    {
        return $event instanceof CharKeyEvent && $event->modifiers === KeyModifiers::CONTROL && $event->char === 'c';
    }

    private function getKeyboardActionFromEvent(Event $event): KeyboardAction
    {
        if ($event instanceof CharKeyEvent) {
            return $this->keybindService->getActionForKey($event->char, $event);
        } elseif ($event instanceof CodedKeyEvent) {
            return $this->keybindService->getActionForKey("", $event);
        }
        return new KeyboardAction(null, $event);
    }

    private function isGlobalAction(KeyboardAction $keyboardAction): bool
    {
        $action = $keyboardAction->getAction();
        return $action !== null && in_array($action, [Keybinds::ACTION_HELP]);
    }





    private function handleGlobalKeybindAction(KeyboardAction $keyboardAction): void {
        if ($keyboardAction === null) {
            return;
        }

        $action = $keyboardAction->getAction();
        if ($action === Keybinds::ACTION_HELP) {
            $this->logger->info(Keybinds::ACTION_HELP);
            $this->showHelp();
        }
    }

    private function showHelp(): void {
        $keybinds = $this->currentIGuiComponent->getKeybindActions();
        $this->previousIGuiComponent = $this->currentIGuiComponent;
        $this->currentIGuiComponent = new HelpComponent($keybinds, function() {
            $this->currentIGuiComponent = $this->previousIGuiComponent;
            $this->previousIGuiComponent = null;
        });
    }

    private function buildLayout(IGuiComponent $IGuiComponent): Widget {
        return GridWidget::default()
            ->direction(Direction::Vertical)
            ->constraints(
                Constraint::percentage(100),
                Constraint::min(1),
            )
            ->widgets(
                $IGuiComponent->build(),
                $this->statusBar->build(),
            );
    }



}