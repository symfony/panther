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

use Symfony\Component\BrowserKit\HttpBrowser as HttpBrowserClient;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\Panther\Client as PantherClient;
use Symfony\Component\Panther\PantherTestCase;

/**
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
abstract class TestCase extends PantherTestCase
{
    protected static string $uploadFileName = 'some-file.txt';
    protected static string $anotherUploadFileName = 'another-file.txt';
    protected static ?string $webServerDir = __DIR__.'/fixtures';

    public static function clientFactoryProvider(): iterable
    {
        // Tests must pass with both Panther and HttpBrowser
        yield 'HttpBrowser' => [[static::class, 'createHttpBrowserClient'], HttpBrowserClient::class];
        yield 'Panther' => [[static::class, 'createPantherClient'], PantherClient::class];

        if ('' === ($_SERVER['SKIP_FIREFOX'] ?? '')) {
            $firefoxFactory = static function (): PantherClient {
                return self::createPantherClient(['browser' => self::FIREFOX]);
            };

            yield 'PantherFirefox' => [$firefoxFactory, PantherClient::class];
        }
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
