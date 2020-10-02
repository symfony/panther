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
        $this->options = array_merge($this->getDefaultOptions(), $options);
        $this->process = new Process([$chromeDriverBinary ?: $this->findChromeDriverBinary(), '--port='.$this->options['port']], null, null, null, null);
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

        $extensions = $this->options['extensions'] ?? [];
        $chromeBinary = $_SERVER['PANTHER_CHROME_BINARY'] ?? '';
        if ($this->arguments || $extensions || '' !== $chromeBinary) {
            $chromeOptions = new ChromeOptions();

            if ($this->arguments) {
                $chromeOptions->addArguments($this->arguments);
            }
            if ('' !== $chromeBinary) {
                $chromeOptions->setBinary($_SERVER['PANTHER_CHROME_BINARY']);
            }

            if ($extensions) {
                if ('' === ($_SERVER['PANTHER_NO_HEADLESS'] ?? '')) {
                    // Chrome headless mode does not support extensions, so this test case is not fully tested.
                    // See https://bugs.chromium.org/p/chromium/issues/detail?id=706008#c5.
                    throw new \InvalidArgumentException('Extensions are only supported in non-headless mode.');
                }

                foreach ($extensions as $path) {
                    if (!file_exists($path)) {
                        throw new \InvalidArgumentException(sprintf('The Chrome extension "%s" does not exist.', $path));
                    }
                }
            }

            $capabilities->setCapability(ChromeOptions::CAPABILITY, $chromeOptions);
        }

        return RemoteWebDriver::create($url, $capabilities, $this->options['connection_timeout_in_ms'] ?? null, $this->options['request_timeout_in_ms'] ?? null);
    }

    public function quit(): void
    {
        $this->process->stop();
    }

    private function findChromeDriverBinary(): string
    {
        if ($binary = $_SERVER['PANTHER_CHROME_DRIVER_BINARY'] ?? null) {
            return $binary;
        }

        switch (PHP_OS_FAMILY) {
            case 'Windows':
                return __DIR__.'/../../chromedriver-bin/chromedriver.exe';
            case 'Darwin':
                return __DIR__.'/../../chromedriver-bin/chromedriver_mac64';
            default:
                return __DIR__.'/../../chromedriver-bin/chromedriver_linux64';
        }
    }

    private function getDefaultArguments(): array
    {
        // Enable the headless mode unless PANTHER_NO_HEADLESS is defined
        $args = ($_SERVER['PANTHER_NO_HEADLESS'] ?? false) ? ['--auto-open-devtools-for-tabs'] : ['--headless', '--window-size=1200,1100', '--disable-gpu'];

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

    private function getDefaultOptions(): array
    {
        return [
            'scheme' => 'http',
            'host' => '127.0.0.1',
            'port' => 9515,
            'path' => '/status',
            'capabilities' => [],
        ];
    }
}
