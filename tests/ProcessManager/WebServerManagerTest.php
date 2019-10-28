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

use Symfony\Component\Panther\ProcessManager\WebServerManager;
use Symfony\Component\Panther\Tests\TestCase;

/**
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
class WebServerManagerTest extends TestCase
{
    public function testRun()
    {
        $server = new WebServerManager(__DIR__.'/../fixtures/', '127.0.0.1', 1234);
        $server->start();
        $this->assertStringContainsString('Hello', (string) file_get_contents('http://127.0.0.1:1234/basic.html'));

        $server->quit();
    }

    public function testAlreadyRunning()
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('The port 1234 is already in use.');

        $server1 = new WebServerManager(__DIR__.'/../fixtures/', '127.0.0.1', 1234);
        $server1->start();

        $server2 = new WebServerManager(__DIR__.'/../fixtures/', '127.0.0.1', 1234);
        try {
            $server2->start();
        } finally {
            $server1->quit();
        }
    }
}
