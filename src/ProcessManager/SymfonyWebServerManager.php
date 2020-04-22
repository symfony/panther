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

use Symfony\Component\Process\ExecutableFinder;
use Symfony\Component\Process\Process;

/**
 * @author Dominik Pfaffenbauer <dominik@pfaffenbauer.at>
 */
final class SymfonyWebServerManager extends AbstractProcessWebServer
{
    /**
     * @throws \RuntimeException
     */
    public function __construct(string $documentRoot, string $hostname, int $port, array $params = [], string $readinessPath = '', array $env = null)
    {
        parent::__construct($hostname, $port, $readinessPath);

        $finder = new ExecutableFinder();
        if (false === $binary = $finder->find('symfony')) {
            throw new \RuntimeException('Unable to find the Symfony binary.');
        }

        if (isset($_SERVER['PANTHER_APP_ENV'])) {
            if (null === $env) {
                $env = [];
            }
            $env['APP_ENV'] = $_SERVER['PANTHER_APP_ENV'];
        }

        $this->process = new Process(
            array_merge(
                [$binary],
                [
                    'server:start',
                    '--allow-http',
                    '--document-root=' . $documentRoot,
                    '--port=' . $port,
                    '--no-tls'
                ]
            ),
            $documentRoot,
            $env,
            null,
            null
        );

        // Symfony Process 3.4 BC: In newer versions env variables always inherit,
        // but in 4.4 inheritEnvironmentVariables is deprecated, but setOptions was removed
        if (\is_callable([$this->process, 'inheritEnvironmentVariables']) && \is_callable([$this->process, 'setOptions'])) {
            $this->process->inheritEnvironmentVariables(true);
        }
    }
}
