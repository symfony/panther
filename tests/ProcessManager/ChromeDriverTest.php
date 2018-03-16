<?php

/*
 * This file is part of the Panthère project.
 *
 * (c) Kévin Dunglas <dunglas@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Panthere\Tests\ProcessManager;

use Facebook\WebDriver\Remote\DesiredCapabilities;
use Facebook\WebDriver\Remote\RemoteWebDriver;
use Panthere\ProcessManager\ChromeDriver;
use PHPUnit\Framework\TestCase;

/**
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
class ChromeDriverTest extends TestCase
{
    public function testRun()
    {
        $driver = new ChromeDriver();
        $driver->run();
        $rwd = RemoteWebDriver::create('http://localhost:9515', DesiredCapabilities::chrome());
        $this->assertNotEmpty($rwd->getCurrentURL());
        $rwd->close();

        $driver->stop();
    }

    /**
     * @expectedException \RuntimeException
     * @expectedExceptionMessage The port 9515 is already in use.
     */
    public function testAlreadyRunning()
    {
        try {
            $driver1 = new ChromeDriver();
            $driver1->run();

            $driver2 = new ChromeDriver();
            $driver2->run();
        } finally {
            $driver1->stop();
        }
    }
}
