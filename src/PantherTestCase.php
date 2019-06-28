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
use PHPUnit\Framework\TestCase;
use Symfony\Bundle\FrameworkBundle\Test\WebTestAssertionsTrait;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Panther\Client as PantherClient;

if (\class_exists(WebTestCase::class)) {
    abstract class PantherTestCase extends WebTestCase
    {
        use PantherTestCaseTrait {
            createPantherClient as private baseCreatePantherClient;
            createGoutteClient as private baseCreateGoutteClient;
        }
        use WebTestAssertionsTrait {
            assertPageTitleSame as private baseAssertPageTitleSame;
            assertPageTitleContains as private baseAssertPageTitleContains;
        }

        protected static function createPantherClient(array $options = [], array $kernelOptions = []): PantherClient
        {
            return self::getClient(self::baseCreatePantherClient($options, $kernelOptions));
        }

        public static function assertPageTitleSame(string $expectedTitle, string $message = ''): void
        {
            $client = self::getClient();
            if ($client instanceof PantherClient) {
                self::assertSame($expectedTitle, $client->getTitle());

                return;
            }

            self::baseAssertPageTitleSame($expectedTitle, $message);
        }

        public static function assertPageTitleContains(string $expectedTitle, string $message = ''): void
        {
            $client = self::getClient();
            if ($client instanceof PantherClient) {
                self::assertStringContainsString($expectedTitle, $client->getTitle());

                return;
            }

            self::baseAssertPageTitleContains($expectedTitle, $message);
        }

        // Need https://github.com/FriendsOfPHP/Goutte/pull/382 first
        /*protected static function createGoutteClient(array $options = [], array $kernelOptions = []): GoutteClient
        {
            return self::getClient(self::baseCreateGoutteClient($options, $kernelOptions));
        }*/
    }
} else {
    abstract class PantherTestCase extends TestCase
    {
        use PantherTestCaseTrait;
    }
}
