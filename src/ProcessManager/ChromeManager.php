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

namespace Symfony\Component\Panthere\ProcessManager;

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
    private $options;

    public function __construct(?string $chromeDriverBinary = null, ?array $arguments = null, array $options = [])
    {
        $this->process = new Process([$chromeDriverBinary ?: $this->findChromeDriverBinary()], null, null, null, null);
        $this->arguments = $arguments ?? $this->getDefaultArguments();
        $this->options = \array_merge($this->getDefaultOptions(), $options);
    }

    /**
     * @throws \RuntimeException
     */
    public function start(): WebDriver
    {
        $url = $this->options['scheme'].'://'.$this->options['host'].':'.$this->options['port'];
        if (!$this->process->isRunning()) {
            $this->checkPortAvailable($this->options['host'], $this->options['port']);
            $this->process->start();
            $this->waitUntilReady($this->process, $url.$this->options['path']);
        }

        $capabilities = DesiredCapabilities::chrome();
        if ($this->arguments) {
            $chromeOptions = new ChromeOptions();
            $chromeOptions->addArguments($this->arguments);
            $capabilities->setCapability(ChromeOptions::CAPABILITY, $chromeOptions);
        }

        return RemoteWebDriver::create($url, $capabilities);
    }

    public function quit(): void
    {
        $this->process->stop();
    }

    private function findChromeDriverBinary(): string
    {
        if ($binary = $_SERVER['PANTHERE_CHROME_DRIVER_BINARY'] ?? null) {
            return $binary;
        }

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
        // Enable the headless mode unless PANTHERE_NO_HEADLESS is defined
        $args = ($_SERVER['PANTHERE_NO_HEADLESS'] ?? false) ? [] : ['--headless', 'window-size=1200,1100', '--disable-gpu'];

        // Disable Chrome's sandbox if PANTHERE_NO_SANDBOX is defined or if running in Travis
        if ($_SERVER['PANTHERE_NO_SANDBOX'] ?? $_SERVER['HAS_JOSH_K_SEAL_OF_APPROVAL'] ?? false) {
            // Running in Travis, disabling the sandbox mode
            $args[] = '--no-sandbox';
        }

        return $args;
    }

    private function getDefaultOptions(): array
    {
        return [
            'scheme' => 'http',
            'host' => '127.0.0.1',
            'port' => 9515,
            'path' => '/status',
        ];
    }
}
