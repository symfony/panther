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

use Symfony\Component\Process\Process;

/**
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
final class ChromeDriver
{
    use WebServerReadinessProbeTrait;

    private $path;

    /**
     * @var Process
     */
    private $process;

    public function __construct(?string $path = null)
    {
        if (null !== $path) {
            $this->path = $path;

            return;
        }

        if (PHP_OS_FAMILY === 'Windows') {
            $this->path = __DIR__.'/../../bin/chromedriver.exe';

            return;
        }

        if (PHP_OS_FAMILY === 'Darwin') {
            $this->path = __DIR__.'/../../bin/chromedriver_mac64';

            return;
        }

        // Assume Linux by default
        $this->path = __DIR__.'/../../bin/chromedriver_linux64';
    }

    /**
     * @throws \RuntimeException
     */
    public function run(): void
    {
        $this->checkPortAvailable('127.0.0.1', 9515);

        $this->process = new Process($this->path, null, null, null, null);
        $this->process->start();

        $this->waitUntilReady($this->process, 'http://127.0.0.1:9515/status');
    }

    /**
     * @throws \RuntimeException
     */
    public function stop(): void
    {
        if (null === $this->process || !$this->process->isStarted()) {
            throw new \RuntimeException('ChromeDriver is not running.');
        }

        $this->process->stop();
    }
}
