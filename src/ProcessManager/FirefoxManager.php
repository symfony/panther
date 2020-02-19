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
use Symfony\Component\Process\Process;

/**
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
final class FirefoxManager implements BrowserManagerInterface
{
    use WebServerReadinessProbeTrait;

    private $process;
    private $arguments;
    private $options;

    public function __construct(?string $geckodriverBinary = null, ?array $arguments = null, array $options = [])
    {
        $this->options = array_merge($this->getDefaultOptions(), $options);
        $this->process = new Process([$geckodriverBinary ?: $this->findGeckodriverBinary(), '--port='.$this->options['port']], null, null, null, null);
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
            $this->waitUntilReady($this->process, $url.$this->options['path'], 'firefox');
        }

        $firefoxOptions = [];
        if (isset($_SERVER['PANTHER_FIREFOX_BINARY'])) {
            $firefoxOptions['binary'] = $_SERVER['PANTHER_FIREFOX_BINARY'];
        }
        if ($this->arguments) {
            $firefoxOptions['args'] = $this->arguments;
        }

        $capabilities = DesiredCapabilities::firefox();
        $capabilities->setCapability('moz:firefoxOptions', $firefoxOptions);

        return RemoteWebDriver::create($url, $capabilities, $this->options['connection_timeout_in_ms'] ?? null, $this->options['request_timeout_in_ms'] ?? null);
    }

    public function quit(): void
    {
        $this->process->stop();
    }

    private function findGeckodriverBinary(): string
    {
        if ($binary = $_SERVER['PANTHER_GECKO_DRIVER_BINARY'] ?? null) {
            return $binary;
        }

        switch (PHP_OS_FAMILY) {
            case 'Windows':
                return __DIR__.'/../../geckodriver-bin/geckodriver.exe';
            case 'Darwin':
                return __DIR__.'/../../geckodriver-bin/geckodriver-macos';
            default:
                return __DIR__.'/../../geckodriver-bin/geckodriver-linux64';
        }
    }

    private function getDefaultArguments(): array
    {
        // Enable the headless mode unless PANTHER_NO_HEADLESS is defined
        $args = ($_SERVER['PANTHER_NO_HEADLESS'] ?? false) ? ['--devtools'] : ['--headless', '--window-size=1200,1100'];

        // Add custom arguments with PANTHER_FIREFOX_ARGUMENTS
        if ($_SERVER['PANTHER_FIREFOX_ARGUMENTS'] ?? false) {
            $arguments = explode(' ', $_SERVER['PANTHER_FIREFOX_ARGUMENTS']);
            $args = array_merge($args, $arguments);
        }

        return $args;
    }

    private function getDefaultOptions(): array
    {
        return [
            'scheme' => 'http',
            'host' => '127.0.0.1',
            'port' => 4444,
            'path' => '/status',
        ];
    }
}
