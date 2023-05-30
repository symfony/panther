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

use Facebook\WebDriver\Chrome\ChromeOptions;
use Facebook\WebDriver\Remote\DesiredCapabilities;
use Facebook\WebDriver\Remote\RemoteWebDriver;
use Facebook\WebDriver\WebDriver;
use Symfony\Component\Process\ExecutableFinder;
use Symfony\Component\Process\Process;

/**
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
final class ChromeManager implements BrowserManagerInterface
{
    use WebServerReadinessProbeTrait;

    private Process $process;
    private array $arguments;
    private array $options;

    /**
     * @throws \RuntimeException
     */
    public function __construct(string $chromeDriverBinary = null, array $arguments = null, array $options = [])
    {
        $this->options = $options ? array_merge($this->getDefaultOptions(), $options) : $this->getDefaultOptions();
        $this->process = $this->createProcess($chromeDriverBinary ?: $this->findChromeDriverBinary());
        $this->arguments = $arguments ?? $this->getDefaultArguments();
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
            $this->waitUntilReady($this->process, $url.$this->options['path'], 'chrome');
        }

        $capabilities = DesiredCapabilities::chrome();

        foreach ($this->options['capabilities'] as $capability => $value) {
            $capabilities->setCapability($capability, $value);
        }

        if ($this->arguments) {
            $chromeOptions = $capabilities->getCapability(ChromeOptions::CAPABILITY);
            if (null === $chromeOptions) {
                $chromeOptions = new ChromeOptions();
                $capabilities->setCapability(ChromeOptions::CAPABILITY, $chromeOptions);
            }
            $chromeOptions->addArguments($this->arguments);

            if (isset($_SERVER['PANTHER_CHROME_BINARY'])) {
                $chromeOptions->setBinary($_SERVER['PANTHER_CHROME_BINARY']);
            }
        }

        return RemoteWebDriver::create($url, $capabilities, $this->options['connection_timeout_in_ms'] ?? null, $this->options['request_timeout_in_ms'] ?? null);
    }

    public function quit(): void
    {
        $this->process->stop();
    }

    /**
     * @throws \RuntimeException
     */
    private function findChromeDriverBinary(): string
    {
        if ($binary = (new ExecutableFinder())->find('chromedriver', null, ['./drivers', './vendor/bin'])) {
            return $binary;
        }

        throw new \RuntimeException('"chromedriver" binary not found. Install it using the package manager of your operating system or by running "composer require --dev dbrekelmans/bdi && vendor/bin/bdi detect drivers".');
    }

    private function getDefaultArguments(): array
    {
        $args = [];

        // Enable the headless mode unless PANTHER_NO_HEADLESS is defined
        if (!($_SERVER['PANTHER_NO_HEADLESS'] ?? false)) {
            $args[] = '--headless';
            $args[] = '--window-size=1200,1100';
            $args[] = '--disable-gpu';
        }

        // Enable devtools for debugging
        if ($_SERVER['PANTHER_DEVTOOLS'] ?? true) {
            $args[] = '--auto-open-devtools-for-tabs';
        }

        // Disable Chrome's sandbox if PANTHER_NO_SANDBOX is defined or if running in Travis
        if ($_SERVER['PANTHER_NO_SANDBOX'] ?? $_SERVER['HAS_JOSH_K_SEAL_OF_APPROVAL'] ?? false) {
            // Running in Travis, disabling the sandbox mode
            $args[] = '--no-sandbox';
        }

        // Add custom arguments with PANTHER_CHROME_ARGUMENTS
        if ($_SERVER['PANTHER_CHROME_ARGUMENTS'] ?? false) {
            $arguments = explode(' ', $_SERVER['PANTHER_CHROME_ARGUMENTS']);
            $args = array_merge($args, $arguments);
        }

        return $args;
    }

    private function createProcess(string $chromeDriverBinary): Process
    {
        $command = array_merge(
            [$chromeDriverBinary, '--port='.$this->options['port']],
            $this->options['chromedriver_arguments']
        );

        return new Process($command, null, null, null, null);
    }

    private function getDefaultOptions(): array
    {
        return [
            'scheme' => 'http',
            'host' => '127.0.0.1',
            'port' => 9515,
            'path' => '/status',
            'chromedriver_arguments' => [],
            'capabilities' => [],
        ];
    }
}
