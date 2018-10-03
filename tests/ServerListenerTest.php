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

namespace Symfony\Component\Panther\Tests;

use PHPUnit\Framework\AssertionFailedError;
use PHPUnit\Framework\Test;
use PHPUnit\Framework\TestSuite;
use Symfony\Component\Panther\PantherTestCase;
use Symfony\Component\Panther\ServerListener;

class ServerListenerTest extends TestCase
{
    public static function tearDownAfterClass()
    {
        PantherTestCase::$stopServerOnTeardown = true;
    }

    public function testStartAndStop(): void
    {
        $testSuite = new TestSuite();
        $listener = new ServerListener();

        $listener->startTestSuite($testSuite);
        static::assertFalse(PantherTestCase::$stopServerOnTeardown);

        $listener->endTestSuite($testSuite);
        static::assertNull(PantherTestCase::$webServerManager);
    }

    /**
     * @dataProvider provideTestPauseOnFailure
     */
    public function testPauseOnFailure(string $method, string $expected): void
    {
        $listener = new ServerListener();
        $listener->testing = true;

        // stores current state
        $argv = $_SERVER['argv'];
        $noHeadless = $_SERVER['PANTHER_NO_HEADLESS'] ?? false;

        self::startWebServer();
        $_SERVER['argv'][] = '--debug';
        $_SERVER['PANTHER_NO_HEADLESS'] = 1;

        $listener->{$method}($this->getMockForAbstractClass(Test::class), new AssertionFailedError('message'), 0);
        $this->expectOutputString($expected);

        // restores previous state
        $_SERVER['argv'] = $argv;
        if (false === $noHeadless) {
            unset($_SERVER['PANTHER_NO_HEADLESS']);
        } else {
            $_SERVER['PANTHER_NO_HEADLESS'] = $noHeadless;
        }
    }

    public function provideTestPauseOnFailure()
    {
        return [
            ['addError', "Error: message\n\nPress enter to continue..."],
            ['addFailure', "Failure: message\n\nPress enter to continue..."],
        ];
    }
}
