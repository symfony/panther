<?php

/*
 * This file is part of the Panthère project.
 *
 * (c) Kévin Dunglas <dunglas@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Panthere;

use Goutte\Client as GoutteClient;
use GuzzleHttp\Client as GuzzleClient;
use Panthere\Client as PanthereClient;
use Panthere\ProcessManager\WebServerManager;

/**
 * Eases conditional class definition.
 *
 * @internal
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
trait PanthereTestCaseTrait
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
     * @var PanthereClient|null
     */
    protected static $panthereClient;

    public static function tearDownAfterClass()
    {
        if (null !== self::$webServerManager) {
            self::$webServerManager->quit();
            self::$webServerManager = null;
        }

        if (null !== self::$panthereClient) {
            self::$panthereClient->quit();
            self::$panthereClient = null;
        }

        if (null !== self::$goutteClient) {
            self::$goutteClient = null;
        }

        self::$baseUri = null;
    }

    protected static function startWebServer(?string $webServerDir = null, string $hostname = '127.0.0.1', int $port = 9000, array $env = []): void {
        if (null !== static::$webServerManager) {
            return;
        }

        if (null === $webServerDir) {
            // Try the local $webServerDir property, or the PANTHERE_WEB_SERVER_DIR env var or default to the Flex directory structure
            $webServerDir = static::$webServerDir ?? $_ENV['PANTHERE_WEB_SERVER_DIR'] ?? __DIR__.'/../../../../public';
        }

        self::$webServerManager = new WebServerManager($webServerDir, $hostname, $port, $env);
        self::$webServerManager->start();

        self::$baseUri = "http://$hostname:$port";
    }

    protected static function createPanthereClient(string $hostname = '127.0.0.1', int $port = 9000): PanthereClient
    {
        self::startWebServer(null, $hostname, $port);
        if (null === self::$panthereClient) {
            self::$panthereClient = Client::createChromeClient(null, null, [], self::$baseUri);
        }

        return self::$panthereClient;
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
