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

namespace Panthere\Tests\ProcessManager;

use Panthere\ProcessManager\WebServerManager;
use PHPUnit\Framework\TestCase;

/**
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
class WebServerManagerTest extends TestCase
{
    public function testRun()
    {
        $server = new WebServerManager(__DIR__.'/../fixtures/', '127.0.0.1', 1234);
        $server->start();
        $this->assertContains('Hello', \file_get_contents('http://127.0.0.1:1234/basic.html'));

        $server->quit();
    }

    /**
     * @expectedException \RuntimeException
     * @expectedExceptionMessage The port 1234 is already in use.
     */
    public function testAlreadyRunning()
    {
        try {
            $server1 = new WebServerManager(__DIR__.'/../fixtures/', '127.0.0.1', 1234);
            $server1->start();

            $server2 = new WebServerManager(__DIR__.'/../fixtures/', '127.0.0.1', 1234);
            $server2->start();
        } finally {
            $server1->quit();
        }
    }
}
