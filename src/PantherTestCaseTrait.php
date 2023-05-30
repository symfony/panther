<?php

/*
 * This file is part of the Panther project.
 *
 * (c) Kévin Dunglas <dunglas@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Symfony\Component\Panther;

use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\BrowserKit\HttpBrowser as HttpBrowserClient;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Component\Panther\Client as PantherClient;
use Symfony\Component\Panther\ProcessManager\ChromeManager;
use Symfony\Component\Panther\ProcessManager\FirefoxManager;
use Symfony\Component\Panther\ProcessManager\WebServerManager;

/**
 * Eases conditional class definition.
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
trait PantherTestCaseTrait
{
    public static bool $stopServerOnTeardown = true;

    protected static ?string $webServerDir = null;

    protected static ?WebServerManager $webServerManager = null;

    protected static ?string $baseUri = null;

    protected static ?HttpBrowserClient $httpBrowserClient = null;

    /**
     * @var PantherClient|null The primary Panther client instance created
     */
    protected static ?PantherClient $pantherClient = null;

    /**
     * @var PantherClient[] All Panther clients, the first one is the primary one (aka self::$pantherClient)
     */
    protected static array $pantherClients = [];

    protected static array $defaultOptions = [
        'webServerDir' => __DIR__.'/../../../../public', // the Flex directory structure
        'hostname' => '127.0.0.1',
        'port' => 9080,
        'router' => '',
        'external_base_uri' => null,
        'readinessPath' => '',
        'browser' => PantherTestCase::CHROME,
        'env' => [],
    ];

    public static function tearDownAfterClass(): void
    {
        if (self::$stopServerOnTeardown) {
            static::stopWebServer();
        }
    }

    public static function stopWebServer(): void
    {
        if (null !== self::$webServerManager) {
            self::$webServerManager->quit();
            self::$webServerManager = null;
        }

        if (null !== self::$pantherClient) {
            foreach (self::$pantherClients as $i => $pantherClient) {
                // Stop ChromeDriver only when all sessions are already closed
                $pantherClient->quit(false);
            }

            self::$pantherClient->getBrowserManager()->quit();
            self::$pantherClient = null;
            self::$pantherClients = [];
        }

        if (null !== self::$httpBrowserClient) {
            self::$httpBrowserClient = null;
        }

        self::$baseUri = null;
    }

    /**
     * @param array $options see {@see $defaultOptions}
     */
    public static function startWebServer(array $options = []): void
    {
        if (null !== static::$webServerManager) {
            return;
        }

        if ($externalBaseUri = $options['external_base_uri'] ?? $_SERVER['PANTHER_EXTERNAL_BASE_URI'] ?? self::$defaultOptions['external_base_uri']) {
            self::$baseUri = $externalBaseUri;

            return;
        }

        $options = [
            'webServerDir' => self::getWebServerDir($options),
            'hostname' => $options['hostname'] ?? self::$defaultOptions['hostname'],
            'port' => (int) ($options['port'] ?? $_SERVER['PANTHER_WEB_SERVER_PORT'] ?? self::$defaultOptions['port']),
            'router' => $options['router'] ?? $_SERVER['PANTHER_WEB_SERVER_ROUTER'] ?? self::$defaultOptions['router'],
            'readinessPath' => $options['readinessPath'] ?? $_SERVER['PANTHER_READINESS_PATH'] ?? self::$defaultOptions['readinessPath'],
            'env' => (array) ($options['env'] ?? self::$defaultOptions['env']),
        ];

        self::$webServerManager = new WebServerManager(...array_values($options));
        self::$webServerManager->start();

        self::$baseUri = sprintf('http://%s:%s', $options['hostname'], $options['port']);
    }

    public static function isWebServerStarted(): bool
    {
        return self::$webServerManager && self::$webServerManager->isStarted();
    }

    public function takeScreenshotIfTestFailed(): void
    {
        if (class_exists(BaseTestRunner::class) && method_exists($this, 'getStatus')) {
            /**
             * PHPUnit <10 TestCase.
             */
            $status = $this->getStatus();
            $isError = BaseTestRunner::STATUS_FAILURE === $status;
            $isFailure = BaseTestRunner::STATUS_ERROR === $status;
        } elseif (method_exists($this, 'status')) {
            /**
             * PHPUnit 10 TestCase.
             */
            $status = $this->status();
            $isError = $status->isError();
            $isFailure = $status->isFailure();
        } else {
            /*
             * Symfony WebTestCase.
             */
            return;
        }
        if ($isError || $isFailure) {
            $type = $isError ? 'error' : 'failure';
            ServerExtensionLegacy::takeScreenshots($type, $this->toString());
        }
    }

    /**
     * Creates the primary browser.
     *
     * @param array $options see {@see $defaultOptions}
     */
    protected static function createPantherClient(array $options = [], array $kernelOptions = [], array $managerOptions = []): PantherClient
    {
        $browser = ($options['browser'] ?? self::$defaultOptions['browser'] ?? PantherTestCase::CHROME);
        $callGetClient = method_exists(self::class, 'getClient') && (new \ReflectionMethod(self::class, 'getClient'))->isStatic();
        if (null !== self::$pantherClient) {
            $browserManager = self::$pantherClient->getBrowserManager();
            if (
                (PantherTestCase::CHROME === $browser && $browserManager instanceof ChromeManager)
                || (PantherTestCase::FIREFOX === $browser && $browserManager instanceof FirefoxManager)
            ) {
                ServerExtension::registerClient(self::$pantherClient);

                /* @phpstan-ignore-next-line */
                return $callGetClient ? self::getClient(self::$pantherClient) : self::$pantherClient;
            }
        }

        self::startWebServer($options);

        if (PantherTestCase::CHROME === $browser) {
            self::$pantherClients[0] = self::$pantherClient = Client::createChromeClient(null, null, $managerOptions, self::$baseUri);
        } else {
            self::$pantherClients[0] = self::$pantherClient = Client::createFirefoxClient(null, null, $managerOptions, self::$baseUri);
        }

        if (is_a(self::class, KernelTestCase::class, true)) {
            static::bootKernel($kernelOptions); // @phpstan-ignore-line
        }

        ServerExtension::registerClient(self::$pantherClient);

        /* @phpstan-ignore-next-line */
        return $callGetClient ? self::getClient(self::$pantherClient) : self::$pantherClient;
    }

    /**
     * Creates an additional browser. Convenient to test apps leveraging Mercure or WebSocket (e.g. a chat).
     */
    protected static function createAdditionalPantherClient(): PantherClient
    {
        if (null === self::$pantherClient) {
            return self::createPantherClient();
        }

        self::$pantherClients[] = self::$pantherClient = new PantherClient(self::$pantherClient->getBrowserManager(), self::$baseUri);

        ServerExtension::registerClient(self::$pantherClient);

        return self::$pantherClient;
    }

    /**
     * @param array $options see {@see $defaultOptions}
     */
    protected static function createHttpBrowserClient(array $options = [], array $kernelOptions = []): HttpBrowserClient
    {
        self::startWebServer($options);

        if (null === self::$httpBrowserClient) {
            // The ScopingHttpClient cant't be used cause the HttpBrowser only supports absolute URLs,
            // https://github.com/symfony/symfony/pull/35177
            self::$httpBrowserClient = new HttpBrowserClient(HttpClient::create());
        }

        if (is_a(self::class, KernelTestCase::class, true)) {
            static::bootKernel($kernelOptions); // @phpstan-ignore-line
        }

        $urlComponents = parse_url(self::$baseUri);
        self::$httpBrowserClient->setServerParameter('HTTP_HOST', sprintf('%s:%s', $urlComponents['host'], $urlComponents['port']));
        if ('https' === $urlComponents['scheme']) {
            self::$httpBrowserClient->setServerParameter('HTTPS', 'true');
        }

        // @phpstan-ignore-next-line
        return method_exists(self::class, 'getClient') && (new \ReflectionMethod(self::class, 'getClient'))->isStatic() ?
            self::getClient(self::$httpBrowserClient) : self::$httpBrowserClient;
    }

    private static function getWebServerDir(array $options): string
    {
        if (isset($options['webServerDir'])) {
            return $options['webServerDir'];
        }

        if (null !== static::$webServerDir) {
            return static::$webServerDir;
        }

        if (!isset($_SERVER['PANTHER_WEB_SERVER_DIR'])) {
            return self::$defaultOptions['webServerDir'];
        }

        if (str_starts_with($_SERVER['PANTHER_WEB_SERVER_DIR'], './')) {
            return getcwd().substr($_SERVER['PANTHER_WEB_SERVER_DIR'], 1);
        }

        return $_SERVER['PANTHER_WEB_SERVER_DIR'];
    }
}
