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

/**
 * @author Dominik Pfaffenbauer <dominik@pfaffenbauer.at>
 */
final class SymfonyWebServerFactory implements WebServerFactoryInterface
{
    public function createNew(array $options): WebServerManagerInterface
    {
        $options = array_merge($options, [
            'allowHttp' => $options['allowHttp'] ?? $_SERVER['PANTHER_SYMFONY_CLI_ALLOW_HTTP'] ?? false,
            'tls' => $options['tls'] ?? $_SERVER['PANTHER_SYMFONY_TLS'] ?? true,
        ]);

        return new SymfonyWebServerManager(...array_values($options));
    }
}
