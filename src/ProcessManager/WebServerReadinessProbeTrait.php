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

use Symfony\Component\HttpClient\HttpClient;
use Symfony\Component\Process\Process;
use Symfony\Contracts\HttpClient\Exception\ExceptionInterface;

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

    public function waitUntilReady(Process $process, string $url, bool $ignoreErrors = false, int $timeout = 5): void
    {
        $client = HttpClient::create(['timeout' => $timeout]);

        $start = microtime(true);

        while (true) {
            $status = $process->getStatus();
            if (Process::STATUS_STARTED !== $status) {
                if (microtime(true) - $start >= $timeout) {
                    throw new \RuntimeException("Chrome could not start (or has crashed) after $timeout seconds.");
                }

                usleep(1000);

                continue;
            }

            $ready = false;

            $response = $client->request('GET', $url);
            $e = $statusCode = null;
            try {
                if (200 === $statusCode = $response->getStatusCode()) {
                    return;
                }
            } catch (ExceptionInterface $e) {
            }

            if (microtime(true) - $start >= $timeout) {
                if ($e) {
                    $message = $e->getMessage();
                } else {
                    $message = "Status code: $statusCode";
                }
                throw new \RuntimeException("Could not connect to chrome after $timeout seconds ($message).");
            }

            usleep(1000);
        }
    }
}
