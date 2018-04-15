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
    private function checkPortAvailable(string $hostname, int $port, bool $throw = true): void
    {
        $resource = @\fsockopen($hostname, $port);
        if (\is_resource($resource)) {
            \fclose($resource);
            if ($throw) {
                throw new \RuntimeException(\sprintf('The port %d is already in use.', $port));
            }
        }
    }

    public function waitUntilReady(Process $process, string $url): void
    {
        $host = parse_url($url, PHP_URL_HOST);
        $port = parse_url($url, PHP_URL_PORT);

        $retries = 0;
        $maxRetries = 5;

        $socketErrors = [];

        do {
            $status = $process->getStatus();

            $socket = fsockopen($host, $port, $errno, $errstr);

            if (Process::STATUS_TERMINATED === $status) {
                throw new \RuntimeException($process->getErrorOutput(), $process->getExitCode());
            }

            if ($errno !== 0) {
                $socketErrors[] = "#$errno:$errstr";
            }

            // block until the web server is ready
            \usleep(1000);
        } while (Process::STATUS_STARTED !== $status || ++$retries === $maxRetries);

        if (count($socketErrors)) {
            throw new \RuntimeException(implode("\n", $socketErrors));
        }

        if ($socket) {
            fclose($socket);
        }
    }
}
