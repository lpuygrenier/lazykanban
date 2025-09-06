<?php
declare(strict_types=1);

namespace Lpuygrenier\Lazykanban;

require __DIR__.'/../vendor/autoload.php';

use Lpuygrenier\Lazykanban\Engine\Engine;
use PhpTui\Term\Terminal;
use PhpTui\Tui\Bridge\PhpTerm\PhpTermBackend as PhpTuiPhpTermBackend;
use PhpTui\Tui\Display\Backend;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use Symfony\Component\Config\FileLocator;

define('PROJECT_ROOT', realpath(__DIR__ . '/../'));

final class App
{
    public static function new(?Terminal $terminal = null, ?Backend $backend = null): Engine
    {
        $terminal = $terminal ?? Terminal::new();

        // Load Symfony Container
        $container = new ContainerBuilder();
        $loader = new YamlFileLoader($container, new FileLocator(PROJECT_ROOT . '/config'));
        $loader->load('services.yaml');

        // Set environment parameters
        $container->setParameter('env(CONFIG_FILE_PATH)', getenv('CONFIG_FILE_PATH') ?: PROJECT_ROOT . '/config/lazykanban.json');
        $container->setParameter('env(KEYBIND_FILE_PATH)', getenv('KEYBIND_FILE_PATH') ?: PROJECT_ROOT . '/config/keybinds.json');
        $container->setParameter('env(LOG_FILE_PATH)', getenv('LOG_FILE_PATH') ?: PROJECT_ROOT . '/logs/lazykanban.log');

        $container->compile();

        // Create and return the Engine with all dependencies injected
        $engine = new Engine(
            $container->get(\Monolog\Logger::class),
            $container->get(\Lpuygrenier\Lazykanban\Service\FileService::class),
            $container->get(\Lpuygrenier\Lazykanban\Service\ConfigService::class),
            $container->get(\Lpuygrenier\Lazykanban\Service\KeybindService::class)
        );

        // Initialize the Engine with terminal and backend
        $engine->initialize($terminal, $backend);

        return $engine;
    }
}

// Initialize and run the application
$engine = App::new();
$engine->run();
