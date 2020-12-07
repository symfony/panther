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

use Symfony\Component\Filesystem\Filesystem;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
class ScreenshotTest extends TestCase
{
    private static $screenshotDir = __DIR__.'/../screenshots';
    private static $screenshotFile = __DIR__.'/../screenshots/screenshot.jpg';

    protected function setUp(): void
    {
        parent::setUp();

        (new Filesystem())->remove(self::$screenshotDir);
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        unset($_SERVER['PANTHER_SCREENSHOT_DIR']);
    }

    public function testTakeScreenshot(): void
    {
        $this->assertFileDoesNotExist(self::$screenshotFile);

        $client = self::createPantherClient();
        $client->request('GET', '/basic.html');
        $client->takeScreenshot(self::$screenshotFile);

        $this->assertFileExists(self::$screenshotFile);
    }

    /**
     * @dataProvider screenshotFileProvider
     */
    public function testCanDefineScreenshotDirAndTakeScreenshot(string $file): void
    {
        $_SERVER['PANTHER_SCREENSHOT_DIR'] = self::$screenshotDir;

        $this->assertFileDoesNotExist(self::$screenshotFile);

        $client = self::createPantherClient();
        $client->request('GET', '/basic.html');
        $client->takeScreenshot($file);

        $this->assertFileExists(self::$screenshotFile);
    }

    /**
     * @dataProvider screenshotFileProvider
     */
    public function testCanDefineRelativeScreenshotDirAndTakeScreenshot(string $file): void
    {
        $_SERVER['PANTHER_SCREENSHOT_DIR'] = './screenshots';

        $this->assertFileDoesNotExist(self::$screenshotFile);

        $client = self::createPantherClient();
        $client->request('GET', '/basic.html');
        $client->takeScreenshot($file);

        $this->assertFileExists(self::$screenshotFile);
    }

    public static function screenshotFileProvider(): iterable
    {
        yield ['screenshot.jpg'];
        yield ['/screenshot.jpg'];
    }
}
