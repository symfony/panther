<?php

namespace Symfony\Component\Panther\Tests;

use PHPUnit\Framework\TestSuite;
use Symfony\Component\Panther\ServerListener;

class ServerListenerTest extends TestCase
{
    private function createTestSuite(): TestSuite
    {
        $suite = $this->createMock(TestSuite::class);
        $suite->expects($this->once())->method('getName')->willReturn('Dummy test suite');

        return $suite;
    }

    public function testStartAndStop(): void
    {
        $this->expectOutputString("Starting Panther server for test suite Dummy test suite...\n\nShutting down Panther server...\n");

        $_SERVER['PANTHER_WEB_SERVER_DIR'] = static::$webServerDir;

        $streamContext = stream_context_create(['http' => [
            'ignore_errors' => true,
            'protocol_version' => '1.1',
            'header' => ['Connection: close'],
            'timeout' => 1,
        ]]);

        $healthCheck = function () use ($streamContext) {
            return @file_get_contents('http://127.0.0.1:9000', false, $streamContext);
        };

        $testSuite = $this->createTestSuite();

        $listener = new ServerListener();
        $listener->startTestSuite($testSuite);

        // Means the server rendered a 404, so server is running.
        static::assertContains('<title>404 Not Found</title>', $healthCheck());

        $listener->endTestSuite($testSuite);

        // False means that ping failed.
        static::assertFalse($healthCheck());
    }
}
