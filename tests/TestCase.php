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

namespace Symfony\Component\Panthere\Tests;

use Goutte\Client as GoutteClient;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\Panthere\Client as PanthereClient;
use Symfony\Component\Panthere\PanthereTestCase;

/**
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
abstract class TestCase extends PanthereTestCase
{
    protected static $webServerDir = __DIR__.'/fixtures';

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
        return $clientFactory()->request('GET', $path);
    }
}
