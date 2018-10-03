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

use Symfony\Component\Process\PhpExecutableFinder;
use Symfony\Component\Process\Process;

/**
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
final class WebServerManager
{
    use WebServerReadinessProbeTrait;

    private $hostname;
    private $port;

    /**
     * @var Process
     */
    private $process;

    /**
     * @throws \RuntimeException
     */
    public function __construct(string $documentRoot, string $hostname, int $port, string $router = '')
    {
        $this->hostname = $hostname;
        $this->port = $port;

        $finder = new PhpExecutableFinder();
        if (false === $binary = $finder->find(false)) {
            throw new \RuntimeException('Unable to find the PHP binary.');
        }

        $this->process = new Process(
            array_filter(array_merge(
                [$binary],
                $finder->findArguments(),
                [
                    '-dvariables_order=EGPCS',
                    '-S',
                    sprintf('%s:%d', $this->hostname, $this->port),
                    '-t',
                    $documentRoot,
                    $router,
                ]
            )),
            $documentRoot,
            null,
            null,
            null
        );
    }

    public function start(): void
    {
        $this->checkPortAvailable($this->hostname, $this->port);
        $this->process->start();

        $this->waitUntilReady($this->process, "http://$this->hostname:$this->port", true);
    }

    /**
     * @throws \RuntimeException
     */
    public function quit(): void
    {
        $this->process->stop();
    }

    public function isStarted()
    {
        return $this->process->isStarted();
    }
}
