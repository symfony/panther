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

namespace Panthere\ProcessManager;

use Facebook\WebDriver\Remote\DesiredCapabilities;
use Facebook\WebDriver\Remote\RemoteWebDriver;
use Facebook\WebDriver\WebDriver;
use \Facebook\WebDriver\WebDriverCapabilities;


final class SeleniumManager implements BrowserManagerInterface
{

    const DEFAULT_HOST = 'http://127.0.0.1:4444/wd/hub';

    private $host;
    private $capabilities;

    public function __construct(?string $host = null, ?WebDriverCapabilities $capabilities = null)
    {
        $host = $host ?? self::DEFAULT_HOST;
        $capabilities = $capabilities ?? DesiredCapabilities::chrome();
        $this->host = $host;
        $this->capabilities = $capabilities;

    }

    public function start(): WebDriver
    {
        return RemoteWebDriver::create(
            $this->host,
            $this->capabilities
        );
    }


    public function quit(): void
    {
        // nothing
    }
}
