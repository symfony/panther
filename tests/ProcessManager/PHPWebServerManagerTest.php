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

use Symfony\Component\Panther\ProcessManager\PHPWebServerManager;
use Symfony\Component\Panther\Tests\TestCase;

/**
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
class PHPWebServerManagerTest extends TestCase
{
    public function testRun()
    {
        $server = new PHPWebServerManager(__DIR__.'/../fixtures/', '127.0.0.1', 1234);
        $server->start();
        $this->assertStringContainsString('Hello', (string) file_get_contents('http://127.0.0.1:1234/basic.html'));

        $server->quit();
    }

    public function testAlreadyRunning()
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('The port 1234 is already in use.');

        $server1 = new PHPWebServerManager(__DIR__.'/../fixtures/', '127.0.0.1', 1234);
        $server1->start();

        $server2 = new PHPWebServerManager(__DIR__.'/../fixtures/', '127.0.0.1', 1234);
        try {
            $server2->start();
        } finally {
            $server1->quit();
        }
        sleep(10);
    }

    public function testPassEnv()
    {
        $server = new PHPWebServerManager(__DIR__.'/../fixtures/', '127.0.0.1', 1234, [], '', ['FOO' => 'bar']);
        $server->start();
        $this->assertStringContainsString('bar', (string) file_get_contents('http://127.0.0.1:1234/env.php?name=FOO'));

        $server->quit();
    }

    public function testPassPantherAppEnv()
    {
        $value = $_SERVER['PANTHER_APP_ENV'] ?? null; // store app env

        $_SERVER['PANTHER_APP_ENV'] = 'dev';
        $server = new PHPWebServerManager(__DIR__.'/../fixtures/', '127.0.0.1', 1234);
        $server->start();
        $this->assertStringContainsString('dev', (string) file_get_contents('http://127.0.0.1:1234/env.php?name=APP_ENV'));

        $server->quit();

        // restore app env
        if (null === $value) {
            unset($_SERVER['PANTHER_APP_ENV']);

            return;
        }
        $_SERVER['PANTHER_APP_ENV'] = $value;
    }

    public function testInvalidDocumentRoot(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessageRegExp('#/not-exists#');

        try {
            $server = new WebServerManager('/not-exists', '127.0.0.1', 1234);
            $server->start();
        } finally {
            $server->quit();
        }
    }
}
