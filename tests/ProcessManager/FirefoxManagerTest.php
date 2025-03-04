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
}
