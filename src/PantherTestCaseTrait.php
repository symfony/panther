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

use Goutte\Client as GoutteClient;
use GuzzleHttp\Client as GuzzleClient;
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
    /**
     * @var bool
     */
    public static $stopServerOnTeardown = true;

    /**
     * @var string|null
     */
    protected static $webServerDir;

    /**
     * @var WebServerManager|null
     */
    protected static $webServerManager;

    /**
     * @var string|null
     */
    protected static $baseUri;

    /**
     * @var GoutteClient|null
     *
     * @deprecated since Panther 0.7
     */
    protected static $goutteClient;

    /**
     * @var HttpBrowserClient|null
     */
    protected static $httpBrowserClient;

    /**
     * @var PantherClient|null The primary Panther client instance created
     */
    protected static $pantherClient;

    /**
     * @var PantherClient[] All Panther clients, the first one is the primary one (aka self::$pantherClient)
     */
    protected static $pantherClients = [];

    /**
     * @var array
     */
    protected static $defaultOptions = [
        'webServerDir' => __DIR__.'/../../../../public', // the Flex directory structure
        'hostname' => '127.0.0.1',
        'port' => 9080,
        'router' => '',
        'external_base_uri' => null,
        'readinessPath' => '',
        'browser' => PantherTestCase::CHROME,
    ];

    public static function tearDownAfterClass(): void
    {
        if (self::$stopServerOnTeardown) {
            static::stopWebServer();
        }
    }

    public static function stopWebServer()
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

        if (null !== self::$goutteClient) {
            self::$goutteClient = null;
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
        ];

        self::$webServerManager = new WebServerManager(...array_values($options));
        self::$webServerManager->start();

        self::$baseUri = sprintf('http://%s:%s', $options['hostname'], $options['port']);
    }

    public static function isWebServerStarted()
    {
        return self::$webServerManager && self::$webServerManager->isStarted();
    }

    /**
     * Creates the primary browser.
     *
     * @param array $options see {@see $defaultOptions}
     */
    protected static function createPantherClient(array $options = [], array $kernelOptions = [], array $managerOptions = []): PantherClient
    {
        $browser = ($options['browser'] ?? self::$defaultOptions['browser'] ?? static::CHROME);
        $callGetClient = \is_callable([self::class, 'getClient']) && (new \ReflectionMethod(self::class, 'getClient'))->isStatic();
        if (null !== self::$pantherClient) {
            $browserManager = self::$pantherClient->getBrowserManager();
            if (
                (static::CHROME === $browser && $browserManager instanceof ChromeManager) ||
                (static::FIREFOX === $browser && $browserManager instanceof FirefoxManager)
            ) {
                return $callGetClient ? self::getClient(self::$pantherClient) : self::$pantherClient;
            }
        }

        self::startWebServer($options);

        if (static::CHROME === $browser) {
            self::$pantherClients[0] = self::$pantherClient = Client::createChromeClient(null, null, $managerOptions, self::$baseUri);
        } else {
            self::$pantherClients[0] = self::$pantherClient = Client::createFirefoxClient(null, null, $managerOptions, self::$baseUri);
        }

        if (\is_a(self::class, KernelTestCase::class, true)) {
            static::bootKernel($kernelOptions); // @phpstan-ignore-line
        }

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

        return self::$pantherClients[] = self::$pantherClient = new PantherClient(self::$pantherClient->getBrowserManager(), self::$baseUri);
    }

    /**
     * @param array $options see {@see $defaultOptions}
     *
     * @deprecated since Panther 0.7, use createHttpBrowserClient instead
     */
    protected static function createGoutteClient(array $options = [], array $kernelOptions = []): GoutteClient
    {
        if (!\class_exists(GoutteClient::class)) {
            throw new \RuntimeException('Goutte is not installed. Run "composer req fabpot/goutte".');
        }

        self::startWebServer($options);
        if (null === self::$goutteClient) {
            $goutteClient = new GoutteClient();
            $goutteClient->setClient(new GuzzleClient(['base_uri' => self::$baseUri]));

            self::$goutteClient = $goutteClient;
        }

        if (\is_a(self::class, KernelTestCase::class, true)) {
            static::bootKernel($kernelOptions); // @phpstan-ignore-line
        }

        // It's not possible to use assertions with Goutte yet, https://github.com/FriendsOfPHP/Goutte/pull/382 needed
        return self::$goutteClient;
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

        if (\is_a(self::class, KernelTestCase::class, true)) {
            static::bootKernel($kernelOptions); // @phpstan-ignore-line
        }

        return self::$httpBrowserClient;
    }

    private static function getWebServerDir(array $options)
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

        if (0 === strpos($_SERVER['PANTHER_WEB_SERVER_DIR'], './')) {
            return getcwd().substr($_SERVER['PANTHER_WEB_SERVER_DIR'], 1);
        }

        return $_SERVER['PANTHER_WEB_SERVER_DIR'];
    }
}
