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
use Panthere\Client;
use Symfony\Component\Process\Process;

/**
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
final class ChromeManager
{
    use WebServerReadinessProbeTrait;

    private $process;

    /**
     * @var Client|null
     */
    private $client;

    public function __construct(?string $chromeDriverBinary = null)
    {
        $this->process = new Process($chromeDriverBinary ?? $this->findChromeDriverBinary(), null, null, null, null);
    }

    /**
     * @param string[]|null $arguments
     *
     * @throws \RuntimeException
     */
    public function start(?array $arguments = null): Client
    {
        if (!$this->process->isStarted()) {
            echo PHP_EOL.PHP_EOL;
            var_dump('starting...');
            echo PHP_EOL.PHP_EOL;

            $this->checkPortAvailable('127.0.0.1', 9515);
            $this->process->start();
            $this->waitUntilReady($this->process, 'http://127.0.0.1:9515/status');
        } else {
            echo PHP_EOL.PHP_EOL;
            var_dump('already started :O');
            echo PHP_EOL.PHP_EOL;
        }

        $capabilities = DesiredCapabilities::chrome();
        if ($args = ($arguments ?? $this->getDefaultArguments())) {
            $chromeOptions = new ChromeOptions();
            $chromeOptions->addArguments(['--headless', '--disable-gpu', '--no-sandbox']);
            $capabilities->setCapability(ChromeOptions::CAPABILITY, $chromeOptions);
        }

        return $this->client = new Client(RemoteWebDriver::create('http://localhost:9515', $capabilities));
    }

    public function quit(): void
    {
        if ($this->client) {
            $this->client->quit();
        }

        $this->process->stop();
        echo PHP_EOL.PHP_EOL;
        var_dump('stopped, waiting...');
        echo PHP_EOL.PHP_EOL;

        $this->waitUntilPortAvailable('127.0.0.1', 9515);

        echo PHP_EOL.PHP_EOL;
        var_dump('port available...');
        echo PHP_EOL.PHP_EOL;
    }

    private function findChromeDriverBinary(): string
    {
        switch (PHP_OS_FAMILY) {
            case 'Windows':
                return __DIR__ . '/../../chromedriver-bin/chromedriver.exe';
                break;

            case 'Darwin':
                return __DIR__ . '/../../chromedriver-bin/chromedriver_mac64';
                break;

            default:
                return __DIR__ . '/../../chromedriver-bin/chromedriver_linux64';
        }
    }

    private function getDefaultArguments(): array
    {
        $args = [];
        if ($_SERVER['CI'] ?? false) {
            // In CI, enable the headless mode
            $args[] = '--headless';
            $args[] = '--disable-gpu';
        }

        if ($_SERVER['HAS_JOSH_K_SEAL_OF_APPROVAL'] ?? false) {
            // Running in Travis, disable the sandbox mode
            $args[] = '--no-sandbox';
        }

        return $args;
    }
}
