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

namespace Symfony\Component\Panther\Tests\ProcessManager;

use Facebook\WebDriver\Firefox\FirefoxOptions;
use Facebook\WebDriver\Remote\DesiredCapabilities;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Panther\Exception\RuntimeException;
use Symfony\Component\Panther\ProcessManager\FirefoxManager;

/**
 * @author Tugdual Saunier <tugdual@saunier.tech>
 */
class FirefoxManagerTest extends TestCase
{
    public function testRun(): void
    {
        $manager = new FirefoxManager();
        $client = $manager->start();
        $this->assertNotEmpty($client->getCurrentURL());
        $manager->quit();
    }

    public function testAlreadyRunning(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('The port 4444 is already in use.');

        $driver1 = new FirefoxManager();
        $driver1->start();

        $driver2 = new FirefoxManager();
        try {
            $driver2->start();
        } finally {
            $driver1->quit();
        }
    }

    public function testNonDefaultPort(): void
    {
        $manager = new FirefoxManager(null, null, ['port' => 4445]);
        $client = $manager->start();
        $this->assertNotEmpty($client->getCurrentURL());
        $manager->quit();
    }

    public function testMultipleInstances(): void
    {
        $driver1 = new FirefoxManager();
        $client1 = $driver1->start();

        $driver2 = new FirefoxManager(null, null, ['port' => 4445]);
        $client2 = $driver2->start();

        $this->assertNotEmpty($client1->getCurrentURL());
        $this->assertNotEmpty($client2->getCurrentURL());

        $driver1->quit();
        $driver2->quit();
    }

    public function testCanOverrideOptions(): void
    {
        $manager = new FirefoxManager(null, null, [
            'capabilities' => [
                'platform' => 'LINUX',
                'browserName' => 'firefox-esr',
                'moz:firefoxOptions' => [
                    'prefs' => [
                        'devtools.console.stdout.content' => true,
                        'reader.parse-on-load.enabled' => true,
                    ],
                    'args' => [
                        '--new-instance',
                    ],
                ],
            ],
        ]);
        $refl = new \ReflectionMethod($manager, 'buildCapabilities');
        $refl->setAccessible(true);
        $capabilities = $refl->invoke($manager);

        $this->assertInstanceOf(DesiredCapabilities::class, $capabilities);
        $this->assertEquals('LINUX', $capabilities->getCapability('platform'));
        $this->assertEquals('firefox-esr', $capabilities->getCapability('browserName'));

        $this->assertInstanceOf(FirefoxOptions::class, $capabilities->getCapability('moz:firefoxOptions'));
        $mozFirefoxOptions = $capabilities->getCapability('moz:firefoxOptions')->toArray();
        $this->assertArrayHasKey('prefs', $mozFirefoxOptions);

        // // our preferences should be set
        $this->assertArrayHasKey('devtools.console.stdout.content', $mozFirefoxOptions['prefs']);
        $this->assertTrue($mozFirefoxOptions['prefs']['devtools.console.stdout.content']);

        // but the default one should still be there
        $this->assertArrayHasKey('ui.prefersReducedMotion', $mozFirefoxOptions['prefs']);
        $this->assertEquals('1', $mozFirefoxOptions['prefs']['ui.prefersReducedMotion']);
        $this->assertArrayHasKey('devtools.jsonview.enabled', $mozFirefoxOptions['prefs']);
        $this->assertFalse($mozFirefoxOptions['prefs']['devtools.jsonview.enabled']);

        // except if we override then
        $this->assertArrayHasKey('reader.parse-on-load.enabled', $mozFirefoxOptions['prefs']);
        $this->assertTrue($mozFirefoxOptions['prefs']['reader.parse-on-load.enabled']);

        // default arguments should still be there
        $this->assertContains('--headless', $mozFirefoxOptions['args']);

        // but our custom one should be there too
        $this->assertContains('--new-instance', $mozFirefoxOptions['args']);
    }
}
