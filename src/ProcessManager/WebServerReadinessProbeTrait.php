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
        $resource = @fsockopen($hostname, $port);
        if (\is_resource($resource)) {
            fclose($resource);
            if ($throw) {
                throw new \RuntimeException(\sprintf('The port %d is already in use.', $port));
            }
        }
    }

    public function waitUntilReady(Process $process, string $url, bool $ignoreErrors = false): void
    {
        $context = stream_context_create(['http' => [
            'ignore_errors' => $ignoreErrors,
            'protocol_version' => '1.1',
            'header' => ['Connection: close'],
            'timeout' => 1,
        ]]);

        while (Process::STATUS_STARTED !== ($status = $process->getStatus()) || false === @file_get_contents($url, false, $context)) {
            if (Process::STATUS_TERMINATED === $status) {
                throw new \RuntimeException($process->getErrorOutput(), $process->getExitCode());
            }

            // block until the web server is ready
            usleep(1000);
        }
        sleep(1);
    }
}
