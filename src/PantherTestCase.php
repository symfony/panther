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
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\ForwardCompatTestTrait;
use Symfony\Bundle\FrameworkBundle\Test\WebTestAssertionsTrait;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\DependencyInjection\Exception\ServiceNotFoundException;
use Symfony\Component\Panther\Client as PantherClient;

if (\class_exists(WebTestCase::class)) {
    if (trait_exists('Symfony\Bundle\FrameworkBundle\Test\WebTestAssertionsTrait') && trait_exists('Symfony\Bundle\FrameworkBundle\Test\ForwardCompatTestTrait')) {
        abstract class PantherTestCase extends WebTestCase
        {
            use ForwardCompatTestTrait;
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
                    if (method_exists(self::class, 'assertStringContainsString')) {
                        self::assertStringContainsString($expectedTitle, $client->getTitle());

                        return;
                    }

                    self::assertContains($expectedTitle, $client->getTitle());

                    return;
                }

                self::baseAssertPageTitleContains($expectedTitle, $message);
            }

            private function doTearDown()
            {
                parent::tearDown();
                self::getClient(null);
            }

            // Copied from WebTestCase to allow assertions to work with createClient

            /**
             * Creates a KernelBrowser.
             *
             * @param array $options An array of options to pass to the createKernel method
             * @param array $server  An array of server parameters
             *
             * @return KernelBrowser A KernelBrowser instance
             */
            protected static function createClient(array $options = [], array $server = [])
            {
                $kernel = static::bootKernel($options);

                try {
                    /**
                     * @var KernelBrowser
                     */
                    $client = $kernel->getContainer()->get('test.client');
                } catch (ServiceNotFoundException $e) {
                    if (class_exists(KernelBrowser::class)) {
                        throw new \LogicException('You cannot create the client used in functional tests if the "framework.test" config is not set to true.');
                    }
                    throw new \LogicException('You cannot create the client used in functional tests if the BrowserKit component is not available. Try running "composer require symfony/browser-kit"');
                }

                $client->setServerParameters($server);

                return self::getClient($client);
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
