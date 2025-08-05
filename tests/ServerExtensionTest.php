<?php

/*
 * This file is part of the Panther project.
 *
 * (c) Kévin Dunglas <kevin@dunglas.dev>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Symfony\Component\Panther\Tests;

use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Component\Panther\PantherTestCase;
use Symfony\Component\Panther\ServerExtensionLegacy;

class ServerExtensionTest extends TestCase
{
    public static function tearDownAfterClass(): void
    {
        PantherTestCase::$stopServerOnTeardown = true;
    }

    public function testStartAndStop(): void
    {
        $extension = new ServerExtensionLegacy();

        $extension->executeBeforeFirstTest();
        static::assertFalse(PantherTestCase::$stopServerOnTeardown);

        $extension->executeAfterLastTest();
        static::assertNull(PantherTestCase::$webServerManager);
    }

    #[DataProvider('provideTestPauseOnFailure')]
    /**
     * @dataProvider provideTestPauseOnFailure
     */
    public function testPauseOnFailure(string $method, string $expected): void
    {
        $extension = new ServerExtensionLegacy();
        $extension->testing = true;

        // stores current state
        $argv = $_SERVER['argv'];
        $noHeadless = filter_var($_SERVER['PANTHER_NO_HEADLESS'] ?? false, \FILTER_VALIDATE_BOOLEAN);

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

    public static function provideTestPauseOnFailure(): iterable
    {
        yield ['executeAfterTestError', "Error: message\n\nPress enter to continue..."];
        yield ['executeAfterTestFailure', "Failure: message\n\nPress enter to continue..."];
    }
}
