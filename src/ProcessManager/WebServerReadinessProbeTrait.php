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
 * @internal
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
trait WebServerReadinessProbeTrait
{
    /**
     * @throws \RuntimeException
     */
    public function checkPortAvailable(string $hostname, int $port): void
    {
        $resource = @\fsockopen($hostname, $port);
        if (\is_resource($resource)) {
            \fclose($resource);
            throw new \RuntimeException(\sprintf('The port %d is already in use.', $port));
        }
    }

    public function waitUntilPortAvailable(string $hostname, int $port): void
    {
        while (true) {
            $resource = @\fsockopen($hostname, $port, $errno, $errstr, 0.001);
            if (!\is_resource($resource)) {
                return;
            }

            \fclose($resource);
        }
    }

    public function waitUntilReady(Process $process, string $url, bool $ignoreErrors = false): void
    {
        $context = \stream_context_create(['http' => [
            'ignore_errors' => $ignoreErrors,
            'protocol_version' => '1.1',
            'header' => ['Connection: close'],
            'timeout' => 1,
        ]]);

        while (Process::STATUS_STARTED !== $process->getStatus() || false === @\file_get_contents($url, false, $context)) {
            // block until the web server is ready
            \usleep(1000);
        }
    }
}
