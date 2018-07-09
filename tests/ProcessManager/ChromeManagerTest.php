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

namespace Symfony\Component\Panthere\Tests\ProcessManager;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Panthere\ProcessManager\ChromeManager;

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

    /**
     * @expectedException \RuntimeException
     * @expectedExceptionMessage The port 9515 is already in use.
     */
    public function testAlreadyRunning()
    {
        $driver1 = new ChromeManager();
        $driver1->start();

        $driver2 = new ChromeManager();
        try {
            $driver2->start();
        } finally {
            $driver1->quit();
        }
    }
}
