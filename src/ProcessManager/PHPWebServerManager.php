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
final class PHPWebServerManager extends AbstractProcessWebServer
{
    /**
     * @throws \RuntimeException
     */
    public function __construct(string $documentRoot, string $hostname, int $port, array $params = [], string $readinessPath = '', array $env = null)
    {
        parent::__construct($hostname, $port, $readinessPath);

        $finder = new PhpExecutableFinder();
        if (false === $binary = $finder->find(false)) {
            throw new \RuntimeException('Unable to find the PHP binary.');
        }

        if (isset($_SERVER['PANTHER_APP_ENV'])) {
            if (null === $env) {
                $env = [];
            }
            $env['APP_ENV'] = $_SERVER['PANTHER_APP_ENV'];
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
                    $params['router'] ?? '',
                ]
            )),
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
