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

namespace Symfony\Component\Panther\Tests;

use Goutte\Client as GoutteClient;
use Symfony\Component\BrowserKit\HttpBrowser as HttpBrowserClient;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\Panther\Client as PantherClient;
use Symfony\Component\Panther\PantherTestCase;

/**
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
abstract class TestCase extends PantherTestCase
{
    protected static $uploadFileName = 'some-file.txt';
    protected static $anotherUploadFileName = 'another-file.txt';
    protected static $webServerDir = __DIR__.'/fixtures';

    /**
     * Compatibility layer with PHPUnit 7.
     */
    public static function assertStringContainsString(string $needle, string $haystack, string $message = ''): void
    {
        if (!method_exists(PantherTestCase::class, 'assertStringContainsString')) {
            self::assertContains($needle, $haystack, $message);

            return;
        }

        parent::assertStringContainsString($needle, $haystack, $message);
    }

    public function clientFactoryProvider(): array
    {
        $firefoxFactory = function (): PantherClient {
            return self::createPantherClient(['browser' => self::FIREFOX]);
        };

        // Tests must pass with both Panther and HttpBrowser
        return [
            'Goutte' => [[static::class, 'createGoutteClient'], GoutteClient::class],
            'HttpBrowser' => [[static::class, 'createHttpBrowserClient'], HttpBrowserClient::class],
            'Panther' => [[static::class, 'createPantherClient'], PantherClient::class],
            'PantherFirefox' => [$firefoxFactory, PantherClient::class],
        ];
    }

    protected function request(callable $clientFactory, string $path): Crawler
    {
        return $clientFactory()->request('GET', self::$baseUri.$path);
    }

    protected function getUploadFilePath(string $fileName): string
    {
        return \sprintf('%s/%s', self::$webServerDir, $fileName);
    }
}
