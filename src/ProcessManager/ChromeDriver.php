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

    /**
     * @var Process
     */
    private $process;

    public function __construct(?string $path = null)
    {
        if (null === $path) {
            switch (PHP_OS_FAMILY) {
                case 'Windows':
                    $path = __DIR__.'/../../bin/chromedriver.exe';
                    break;

                case 'Darwin':
                    $path = __DIR__.'/../../bin/chromedriver_mac64';
                    break;

                default:
                    $path = __DIR__.'/../../bin/chromedriver_linux64';
                    break;
            }
        }

        $this->process = new Process($path, null, null, null, null);
    }

    /**
     * @throws \RuntimeException
     */
    public function run(): void
    {
        $this->checkPortAvailable('127.0.0.1', 9515);

        $this->process->start();
        $this->waitUntilReady($this->process, 'http://127.0.0.1:9515/status');
    }

    public function stop(): void
    {
        $this->process->stop();
        $this->waitUntilPortAvailable('127.0.0.1', 9515);
    }
}
