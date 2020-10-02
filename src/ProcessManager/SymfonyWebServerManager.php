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
    public function __construct(
        string $documentRoot,
        string $hostname,
        int $port,
        string $router = '',
        string $readinessPath = '',
        array $env = null,
        bool $allowHttp = false,
        bool $tls = true
    )
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

        $processParams = [
             'server:start',
            '--document-root=' . $documentRoot,
            '--port=' . $port
        ];

        if (!$tls) {
            $processParams[] = '--no-tls';
        }

        if ($allowHttp) {
            $processParams[] = '--allow-http';
        }

        if ('' !== $router) {
            $processParams[] = '--passthru ' . $router;
        }

        $this->process = new Process(
            array_merge(
                [$binary],
                $processParams
            ),
            $documentRoot,
            $env,
            null,
            null
        );
    }
}
