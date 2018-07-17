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

namespace Symfony\Component\Panther\ProcessManager;

use Facebook\WebDriver\Remote\DesiredCapabilities;
use Facebook\WebDriver\Remote\RemoteWebDriver;
use Facebook\WebDriver\WebDriver;
use Facebook\WebDriver\WebDriverCapabilities;

/**
 * @author Dmitry Kuzmin <rockwith@me.com>
 */
final class SeleniumManager implements BrowserManagerInterface
{
    private $host;
    private $capabilities;

    public function __construct(
        ?string $host = 'http://127.0.0.1:4444/wd/hub',
        ?WebDriverCapabilities $capabilities = null
    ) {
        $this->host = $host;
        $this->capabilities = $capabilities ?? DesiredCapabilities::chrome();
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
