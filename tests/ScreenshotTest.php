<?php

namespace Symfony\Component\Panther\Tests;

use Symfony\Component\Filesystem\Filesystem;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
class ScreenshotTest extends TestCase
{
    private static $screenshotDir = __DIR__.'/../screenshots';

    protected function setUp(): void
    {
        parent::setUp();

        (new Filesystem())->remove(self::$screenshotDir);
    }

    public function testTakeScreenshot(): void
    {
        $file = self::$screenshotDir.'/screenshot.jpg';

        $this->assertFileDoesNotExist($file);

        $client = self::createPantherClient();
        $client->request('GET', '/basic.html');
        $client->takeScreenshot($file);

        $this->assertFileExists($file);
    }
}
