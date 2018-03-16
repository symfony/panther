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

namespace Panthere\Tests;

use Goutte\Client as GoutteClient;
use Panthere\Client as PanthereClient;
use Panthere\PanthereTestCase;
use Symfony\Component\DomCrawler\Crawler;

/**
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
abstract class TestCase extends PanthereTestCase
{
    protected static $webServerDir = __DIR__.'/fixtures';

    /**
     * @return callable[]
     */
    public function clientFactoryProvider(): array
    {
        // Tests must pass with both Panthere and Goutte
        return [
            [[static::class, 'createGoutteClient'], GoutteClient::class],
            [[static::class, 'createPanthereClient'], PanthereClient::class],
        ];
    }

    protected function request(callable $clientFactory, string $path): Crawler
    {
        return $clientFactory()->request('GET', static::$baseUri.$path);
    }
}
