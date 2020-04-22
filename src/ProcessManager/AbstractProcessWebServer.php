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

use Symfony\Component\Process\Process;

/**
 * @author Dominik Pfaffenbauer <dominik@pfaffenbauer.at>
 */
abstract class AbstractProcessWebServer implements WebServerManagerInterface
{
    use WebServerReadinessProbeTrait;

    protected $hostname;
    protected $port;
    protected $readinessPath;

    /**
     * @var Process
     */
    protected $process;

    public function __construct(string $hostname, int $port, string $readinessPath)
    {
        $this->hostname = $hostname;
        $this->port = $port;
        $this->readinessPath = $readinessPath;
    }

    public function start(): void
    {
        $this->checkPortAvailable($this->hostname, $this->port);
        $this->process->start();

        $url = "http://$this->hostname:$this->port";

        if ($this->readinessPath) {
            $url .= $this->readinessPath;
        }

        $this->waitUntilReady($this->process, $url, 'web server', true);
    }

    /**
     * @throws \RuntimeException
     */
    public function quit(): void
    {
        $this->process->stop();
    }

    public function isStarted(): bool
    {
        return $this->process->isStarted();
    }
}
