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

use PHPUnit\Framework\TestCase;
use Symfony\Component\Panther\ProcessManager\ChromeManager;

/**
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
class ChromeManagerTest extends TestCase
{
    public function testRun()
    {
        $manager = new ChromeManager();
        $client = $manager->start();
        $this->assertNotEmpty($client->getCurrentURL());
        $manager->quit();
    }

    public function testAlreadyRunning()
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('The port 9515 is already in use.');

        $driver1 = new ChromeManager();
        $driver1->start();

        $driver2 = new ChromeManager();
        try {
            $driver2->start();
        } finally {
            $driver1->quit();
        }
    }

    public function testNonDefaultPort()
    {
        $manager = new ChromeManager(null, null, ['port' => 9516]);
        $client = $manager->start();
        $this->assertNotEmpty($client->getCurrentURL());
        $manager->quit();
    }

    public function testMultipleInstances()
    {
        $driver1 = new ChromeManager();
        $client1 = $driver1->start();

        $driver2 = new ChromeManager(null, null, ['port' => 9516]);
        $client2 = $driver2->start();

        $this->assertNotEmpty($client1->getCurrentURL());
        $this->assertNotEmpty($client2->getCurrentURL());

        $driver1->quit();
        $driver2->quit();
    }

    public function testSetExtensions()
    {
        if ('' === ($_SERVER['PANTHER_NO_HEADLESS'] ?? '')) {
            // Chrome headless mode does not support extensions, so this test case is not fully tested.
            // See https://bugs.chromium.org/p/chromium/issues/detail?id=706008#c5.
            $this->markTestSkipped('Extensions are only supported in non-headless mode.');
        }

        $manager = new ChromeManager(null, null, ['extensions' => [__DIR__.'/../fixtures/chrome/extension.crx']]);

        $client = $manager->start();
        $client->get('chrome-extension://bkkidjjhlndbkocoaphmdmmdgglimihb/manifest.json');

        $this->assertStringContainsString('Getting Started Example', $client->getPageSource());
        $this->assertNotEmpty($client->getCurrentURL());

        $manager->quit();
    }

    public function testSetInvalidExtensions()
    {
        $this->expectException(\InvalidArgumentException::class);
        $m = new ChromeManager(null, null, ['extensions' => [__DIR__.'/../fixtures/chrome/invalid-extension.crx']]);
        $m->start();
    }
}
