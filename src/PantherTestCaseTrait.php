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
     */
    protected static $goutteClient;

    /**
     * @var PantherClient|null
     */
    protected static $pantherClient;

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
            self::$pantherClient->quit();
            self::$pantherClient = null;
        }

        if (null !== self::$goutteClient) {
            self::$goutteClient = null;
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
            'webServerDir' => $options['webServerDir'] ?? static::$webServerDir ?? $_SERVER['PANTHER_WEB_SERVER_DIR'] ?? self::$defaultOptions['webServerDir'],
            'hostname' => $options['hostname'] ?? self::$defaultOptions['hostname'],
            'port' => (int) ($options['port'] ?? $_SERVER['PANTHER_WEB_SERVER_PORT'] ?? self::$defaultOptions['port']),
            'router' => $options['router'] ?? $_SERVER['PANTHER_WEB_SERVER_ROUTER'] ?? self::$defaultOptions['router'],
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
     * @param array $options       see {@see $defaultOptions}
     * @param array $kernelOptions
     */
    protected static function createPantherClient(array $options = [], array $kernelOptions = []): PantherClient
    {
        self::startWebServer($options);
        if (null === self::$pantherClient) {
            self::$pantherClient = Client::createChromeClient(null, null, [], self::$baseUri);
        }

        if (\is_a(self::class, KernelTestCase::class, true)) {
            static::bootKernel($kernelOptions);
        }

        return self::$pantherClient;
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

        self::startWebServer($options);
        if (null === self::$goutteClient) {
            $goutteClient = new GoutteClient();
            $goutteClient->setClient(new GuzzleClient(['base_uri' => self::$baseUri]));

            self::$goutteClient = $goutteClient;
        }

        if (\is_a(self::class, KernelTestCase::class, true)) {
            static::bootKernel($kernelOptions);
        }

        return self::$goutteClient;
    }
}
