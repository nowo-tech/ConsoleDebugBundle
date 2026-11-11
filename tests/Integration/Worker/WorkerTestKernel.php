<?php

declare(strict_types=1);

namespace Nowo\ConsoleDebugBundle\Tests\Integration\Worker;

use Nowo\ConsoleDebugBundle\ConsoleDebug;
use Nowo\ConsoleDebugBundle\ConsoleDebugRegistry;
use Nowo\ConsoleDebugBundle\NowoConsoleDebugBundle;
use Psr\Log\NullLogger;
use Symfony\Bundle\FrameworkBundle\FrameworkBundle;
use Symfony\Bundle\FrameworkBundle\Kernel\MicroKernelTrait;
use Symfony\Component\DependencyInjection\Compiler\PassConfig;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Kernel;
use Symfony\Component\Routing\Loader\Configurator\RoutingConfigurator;

use function assert;
use function sys_get_temp_dir;

/**
 * Kernel of the kept-alive worker test: FrameworkBundle + this bundle with an always-open custom gate, and
 * services_resetter replaced by {@see NoopServicesResetter} (a host without the resetter). Pages:
 * "/page/A" records a private cdbg() entry and answers JSON (entries never injected) or HTML; "/page/B" answers HTML
 * without any cdbg() call and must never carry user A's entries.
 */
final class WorkerTestKernel extends Kernel
{
    use MicroKernelTrait;

    public const REGISTRY      = 'test.worker.console_debug_registry';
    public const CONSOLE_DEBUG = 'test.worker.console_debug';

    public function registerBundles(): iterable
    {
        return [new FrameworkBundle(), new NowoConsoleDebugBundle()];
    }

    public function getProjectDir(): string
    {
        return __DIR__;
    }

    public function getCacheDir(): string
    {
        return sys_get_temp_dir() . '/nowo-console-debug-worker/cache/' . $this->environment;
    }

    public function getLogDir(): string
    {
        return sys_get_temp_dir() . '/nowo-console-debug-worker/log';
    }

    public function page(string $user, string $format): Response
    {
        if ($user === 'A') {
            $consoleDebug = $this->getContainer()->get(self::CONSOLE_DEBUG);
            assert($consoleDebug instanceof ConsoleDebug);
            $consoleDebug->log('private-entry-of-user-A', ['email' => 'user-a@example.test']);
        }

        if ($format === 'json') {
            return new JsonResponse(['user' => $user]);
        }

        return new Response('<html><body><h1>Page of user ' . $user . '</h1></body></html>', 200, ['Content-Type' => 'text/html; charset=UTF-8']);
    }

    protected function configureContainer(ContainerConfigurator $container): void
    {
        $container->extension('framework', [
            'secret'                => 'REDACTED-TEST-x',
            'test'                  => true,
            'http_method_override'  => false,
            'handle_all_throwables' => true,
            'php_errors'            => ['log' => true],
        ]);
        $container->extension('nowo_console_debug', [
            'enabled'      => true,
            'gate_service' => OpenConsoleDebugGate::class,
        ]);
        $services = $container->services();
        // Quiet logger: the framework's Messenger failure listener logs every failing test message as critical.
        $services->set('logger', NullLogger::class);
        $services->set(OpenConsoleDebugGate::class);
        $services->alias(self::REGISTRY, ConsoleDebugRegistry::class)->public();
        $services->alias(self::CONSOLE_DEBUG, ConsoleDebug::class)->public();
    }

    protected function configureRoutes(RoutingConfigurator $routes): void
    {
        $routes->add('test_page', '/page/{user}.{format}')->controller('kernel::page');
    }

    protected function build(ContainerBuilder $container): void
    {
        $container->addCompilerPass(new ReplaceServicesResetterPass(), PassConfig::TYPE_BEFORE_REMOVING, -1024);
    }
}
