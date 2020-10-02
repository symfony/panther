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
final class PhpWebServerFactory implements WebServerFactoryInterface
{
    public function createNew(array $options): WebServerManagerInterface
    {
        return new PhpWebServerManager(...array_values($options));
    }
}
