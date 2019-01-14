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
use Symfony\Component\Panther\Client as PantherClient;
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
    public static $stopServersOnTeardown = true;

    /**
     * @var string|null
     */
    protected static $webServerDir = null;

    /**
     * @var WebServerManager[]
     */
    protected static $webServerManagers = [];

    /**
     * The last started web server base uri.
     *
     * @var string|null
     */
    protected static $baseUri;

    /**
     * @var GoutteClient[]
     */
    protected static $goutteClients = [];

    /**
     * @var PantherClient[]
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
    ];

    public static function tearDownAfterClass()
    {
        if (self::$stopServersOnTeardown) {
            static::stopWebServers();
        }
    }

    public static function stopWebServers(): void
    {
        foreach (self::$webServerManagers as $webServerManager) {
            $webServerManager->quit();
        }

        self::$webServerManagers = [];

        foreach (self::$pantherClients as $pantherClient) {
            $pantherClient->quit();
        }

        self::$pantherClients = [];

        self::$goutteClients = [];

        self::$baseUri = null;
    }

    /**
     * @param array $options see {@see $defaultOptions}
     */
    public static function startWebServer(array $options = []): string
    {
        if ($externalBaseUri = $options['external_base_uri'] ?? $_SERVER['PANTHER_EXTERNAL_BASE_URI'] ?? self::$defaultOptions['external_base_uri']) {
            self::$baseUri = $externalBaseUri;

            return $externalBaseUri;
        }

        $hostname = $options['hostname'] ?? self::$defaultOptions['hostname'];
        $port = (int) ($options['port'] ?? $_SERVER['PANTHER_WEB_SERVER_PORT'] ?? self::$defaultOptions['port']);

        $baseUri = sprintf('http://%s:%s', $hostname, $port);
        if (!isset(self::$webServerManagers[$baseUri])) {
            $webServerDir = $options['webServerDir'] ?? static::$webServerDir ?? $_SERVER['PANTHER_WEB_SERVER_DIR'] ?? self::$defaultOptions['webServerDir'];
            $router = $options['router'] ?? $_SERVER['PANTHER_WEB_SERVER_ROUTER'] ?? self::$defaultOptions['router'];

            $webServerManager = new WebServerManager($webServerDir, $hostname, $port, $router);
            $webServerManager->start();

            self::$baseUri = $baseUri;

            self::$webServerManagers[$baseUri] = $webServerManager;
        }

        return $baseUri;
    }

    public static function isWebServerStarted(): bool
    {
        foreach (self::$webServerManagers as $webServerManager) {
            if ($webServerManager->isStarted()) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param array $options       see {@see $defaultOptions}
     * @param array $kernelOptions
     */
    protected static function createPantherClient(array $options = [], array $kernelOptions = []): PantherClient
    {
        $baseUri = self::startWebServer($options);

        if (!isset(self::$pantherClients[$baseUri])) {
            self::$pantherClients[$baseUri] = Client::createChromeClient(null, null, [], $baseUri);
        }

        if (\is_a(self::class, KernelTestCase::class, true)) {
            static::bootKernel($kernelOptions);
        }

        return self::$pantherClients[$baseUri];
    }

    /**
     * @param array $options       see {@see $defaultOptions}
     * @param array $kernelOptions
     */
    protected static function createGoutteClient(array $options = [], array $kernelOptions = []): GoutteClient
    {
        if (!\class_exists(GoutteClient::class)) {
            throw new \RuntimeException('Goutte is not installed. Run "composer req fabpot/goutte".');
        }

        $baseUri = self::startWebServer($options);

        if (!isset(self::$goutteClients[$baseUri])) {
            $goutteClient = new GoutteClient();
            $goutteClient->setClient(new GuzzleClient(['base_uri' => $baseUri]));

            self::$goutteClients[$baseUri] = $goutteClient;
        }

        if (\is_a(self::class, KernelTestCase::class, true)) {
            static::bootKernel($kernelOptions);
        }

        return self::$goutteClients[$baseUri];
    }
}
