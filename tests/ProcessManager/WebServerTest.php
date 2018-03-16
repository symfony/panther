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

use Panthere\ProcessManager\WebServer;
use PHPUnit\Framework\TestCase;

/**
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
class WebServerTest extends TestCase
{
    public function testRun()
    {
        $server = new WebServer(__DIR__.'/../fixtures/', '127.0.0.1', 1234);
        $server->run();
        $this->assertContains('Hello', \file_get_contents('http://127.0.0.1:1234/basic.html'));

        $server->stop();
    }

    /**
     * @expectedException \RuntimeException
     * @expectedExceptionMessage The port 1234 is already in use.
     */
    public function testAlreadyRunning()
    {
        try {
            $server1 = new WebServer(__DIR__.'/../fixtures/', '127.0.0.1', 1234);
            $server1->run();

            $server2 = new WebServer(__DIR__.'/../fixtures/', '127.0.0.1', 1234);
            $server2->run();
        } finally {
            $server1->stop();
        }
    }
}
