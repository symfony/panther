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

use Symfony\Component\Panther\PantherTestCase;
use Symfony\Component\Panther\ServerExtension;

class ServerExtensionTest extends TestCase
{
    public static function tearDownAfterClass()
    {
        PantherTestCase::$stopServerOnTeardown = true;
    }

    public function testStartAndStop(): void
    {
        $extension = new ServerExtension();

        $extension->executeBeforeFirstTest();
        static::assertFalse(PantherTestCase::$stopServerOnTeardown);

        $extension->executeAfterLastTest();
        static::assertNull(PantherTestCase::$webServerManager);
    }

    /**
     * @dataProvider provideTestPauseOnFailure
     */
    public function testPauseOnFailure(string $method, string $expected): void
    {
        $extension = new ServerExtension();
        $extension->testing = true;

        // stores current state
        $argv = $_SERVER['argv'];
        $noHeadless = $_SERVER['PANTHER_NO_HEADLESS'] ?? false;

        self::startWebServer();
        $_SERVER['argv'][] = '--debug';
        $_SERVER['PANTHER_NO_HEADLESS'] = 1;

        $extension->{$method}('test', 'message', 0);
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
            ['executeAfterTestError', "Error: message\n\nPress enter to continue..."],
            ['executeAfterTestFailure', "Failure: message\n\nPress enter to continue..."],
        ];
    }
}
