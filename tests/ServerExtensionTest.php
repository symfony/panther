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

use Facebook\WebDriver\WebDriver;
use Symfony\Component\Panther\PantherTestCase;
use Symfony\Component\Panther\ServerExtension;

class ServerExtensionTest extends TestCase
{
    public static function tearDownAfterClass(): void
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

    public function provideTestPauseOnFailure(): iterable
    {
        yield ['executeAfterTestError', "Error: message\n\nPress enter to continue..."];
        yield ['executeAfterTestFailure', "Failure: message\n\nPress enter to continue..."];
    }

    public function testScreenshotTaking(): void
    {
        $clientMock = $this->createMock(WebDriver::class);
        $clientMock->expects($this->once())
                   ->method('takeScreenshot');

        $problematicTestNameString = 'AcmeTest-EndToEnd-TestCases-Orders-Admin-AddOrderTest__testAddOrder with data set "europe/\@!;:\" (\'EUROPE_TEST_CLIENT\', AcmeTest-EndToEnd-Library-Modules-Orders-Admin-Add-Models-Initial-TransferObject-BillingInformation Object (...), AcmeTest-EndToEnd-Library-Modules-Orders-Admin-Add-Models-Initial-TransferObject-ShippingAddress Object (...), \'Another argument\', \'Last argument\')';
        $actualFilePath = ServerExtension::takeClientScreenshot('screenshot_dir', 'failure', $problematicTestNameString, $clientMock, 1);
        $this->assertStringEndsWith(
            '_failure_AcmeTest-EndToEnd-TestCases-Orders-Admin-AddOrderTest__testAddOrder with data set europe-_- EUROPE_TEST_CLIENT AcmeTest-EndToEnd-Library-Modules-Order_afc257711399c8d413c87167d5fea6d1-1.png',
            $actualFilePath
        );
    }
}
