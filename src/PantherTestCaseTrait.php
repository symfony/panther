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
use Symfony\Component\Panther\Client as PantherClient;
use Symfony\Component\Panther\ProcessManager\WebServerManager;

/**
 * Eases conditional class definition.
 *
 * @internal
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
trait PantherTestCaseTrait
{
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

    public static function tearDownAfterClass()
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

    protected static function startWebServer(?string $webServerDir = null, string $hostname = '127.0.0.1', int $port = 9000): void
    {
        if (null !== static::$webServerManager) {
            return;
        }

        if (null === $webServerDir) {
            // Try the local $webServerDir property, or the PANTHER_WEB_SERVER_DIR env var or default to the Flex directory structure
            $webServerDir = static::$webServerDir ?? $_SERVER['PANTHER_WEB_SERVER_DIR'] ?? __DIR__.'/../../../../public';
        }

        self::$webServerManager = new WebServerManager($webServerDir, $hostname, $port);
        self::$webServerManager->start();

        self::$baseUri = "http://$hostname:$port";
    }

    protected static function createPantherClient(string $hostname = '127.0.0.1', int $port = 9000): PantherClient
    {
        self::startWebServer(null, $hostname, $port);
        if (null === self::$pantherClient) {
            self::$pantherClient = Client::createChromeClient(null, null, [], self::$baseUri);
        }

        return self::$pantherClient;
    }

    protected static function createGoutteClient(): GoutteClient
    {
        if (!\class_exists(GoutteClient::class)) {
            throw new \RuntimeException('Goutte is not installed. Run "composer req fabpot/goutte".');
        }

        self::startWebServer();
        if (null === self::$goutteClient) {
            $goutteClient = new GoutteClient();
            $goutteClient->setClient(new GuzzleClient(['base_uri' => self::$baseUri]));

            self::$goutteClient = $goutteClient;
        }

        return self::$goutteClient;
    }
}
