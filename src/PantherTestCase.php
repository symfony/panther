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

use PHPUnit\Framework\TestCase;
use Symfony\Bundle\FrameworkBundle\Test\WebTestAssertionsTrait;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\BrowserKit\AbstractBrowser;
use Symfony\Component\Panther\Client as PantherClient;

if (\class_exists(WebTestCase::class)) {
    // Compatibility with buggy 4.3 versions, see https://github.com/symfony/symfony/pull/33278
    $canUseAssertions = false;
    try {
        $canUseAssertions = AbstractBrowser::class === (new \ReflectionMethod(WebTestCase::class, 'getClient'))->getReturnType()->getName();
    } catch (\ReflectionException $e) {
        // Old version of WebTestCase
    }

    if ($canUseAssertions) {
        abstract class PantherTestCase extends WebTestCase
        {
            use PantherTestCaseTrait;
            use WebTestAssertionsTrait {
                assertPageTitleSame as private baseAssertPageTitleSame;
                assertPageTitleContains as private baseAssertPageTitleContains;
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
        }
    } else {
        abstract class PantherTestCase extends WebTestCase
        {
            use PantherTestCaseTrait;
        }
    }
} else {
    abstract class PantherTestCase extends TestCase
    {
        use PantherTestCaseTrait;
    }
}
