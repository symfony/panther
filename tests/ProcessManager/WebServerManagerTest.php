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

use Symfony\Component\Panther\Exception\RuntimeException;
use Symfony\Component\Panther\ProcessManager\WebServerManager;
use Symfony\Component\Panther\Tests\TestCase;

/**
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
class WebServerManagerTest extends TestCase
{
    public function testRun(): void
    {
        $server = new WebServerManager(__DIR__.'/../fixtures/', '127.0.0.1', 1234);
        $server->start();
        $this->assertStringContainsString('Hello', (string) file_get_contents('http://127.0.0.1:1234/basic.html'));

        $server->quit();
    }

    public function testAlreadyRunning(): void
    {
        $this->expectException(RuntimeException::class);
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

    public function testPassEnv(): void
    {
        $server = new WebServerManager(__DIR__.'/../fixtures/', '127.0.0.1', 1234, '', '', ['FOO' => 'bar']);
        $server->start();
        $this->assertStringContainsString('bar', (string) file_get_contents('http://127.0.0.1:1234/env.php?name=FOO'));

        $server->quit();
    }

    public function testPassPantherAppEnv(): void
    {
        $value = $_SERVER['PANTHER_APP_ENV'] ?? null; // store app env

        $_SERVER['PANTHER_APP_ENV'] = 'dev';
        $server = new WebServerManager(__DIR__.'/../fixtures/', '127.0.0.1', 1234);
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
        $this->expectException(\Symfony\Component\Process\Exception\RuntimeException::class);
        $this->expectExceptionMessageMatches('#/not-exists#');

        $server = new WebServerManager('/not-exists', '127.0.0.1', 1234);
        try {
            $server->start();
        } finally {
            $server->quit();
        }
    }
}
