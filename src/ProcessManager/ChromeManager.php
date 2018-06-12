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

use Facebook\WebDriver\Chrome\ChromeOptions;
use Facebook\WebDriver\Remote\DesiredCapabilities;
use Facebook\WebDriver\Remote\RemoteWebDriver;
use Facebook\WebDriver\WebDriver;
use Symfony\Component\Process\Process;

/**
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
final class ChromeManager implements BrowserManagerInterface
{
    use WebServerReadinessProbeTrait;

    private $process;
    private $arguments;

    public function __construct(?string $chromeDriverBinary = null, ?array $arguments = null)
    {
        $this->process = new Process([$chromeDriverBinary ?? $this->findChromeDriverBinary()], null, null, null, null);
        $this->arguments = $arguments ?? $this->getDefaultArguments();
    }

    /**
     * @param string[]|null $arguments
     *
     * @throws \RuntimeException
     */
    public function start(): WebDriver
    {
        if (!$this->process->isRunning()) {
            $this->checkPortAvailable('127.0.0.1', 9515);
            $this->process->start();
            $this->waitUntilReady($this->process, 'http://127.0.0.1:9515/status');
        }

        $capabilities = DesiredCapabilities::chrome();
        if ($this->arguments) {
            $chromeOptions = new ChromeOptions();
            $chromeOptions->addArguments($this->arguments);
            $capabilities->setCapability(ChromeOptions::CAPABILITY, $chromeOptions);
        }

        return RemoteWebDriver::create('http://localhost:9515', $capabilities);
    }

    public function quit(): void
    {
        $this->process->stop();
    }

    private function findChromeDriverBinary(): string
    {
        switch (PHP_OS_FAMILY) {
            case 'Windows':
                return __DIR__.'/../../chromedriver-bin/chromedriver.exe';
                break;
            case 'Darwin':
                return __DIR__.'/../../chromedriver-bin/chromedriver_mac64';
                break;
            default:
                return __DIR__.'/../../chromedriver-bin/chromedriver_linux64';
        }
    }

    private function getDefaultArguments(): array
    {
        // Enable the headless mode
        $args = ['--headless', 'window-size=1200,1100', '--disable-gpu'];

        if ($_SERVER['HAS_JOSH_K_SEAL_OF_APPROVAL'] ?? false) {
            // Running in Travis, disabling the sandbox mode
            $args[] = '--no-sandbox';
        }

        return $args;
    }
}
