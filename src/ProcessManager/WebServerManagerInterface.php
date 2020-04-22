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
 * A web-server manager (for instance using PHP Web Server or Symfony CLI Web Server).
 *
 * @author Dominik Pfaffenbauer <dominik@pfaffenbauer.at>
 */
interface WebServerManagerInterface
{
    /**
     * @throws \RuntimeException
     */
    public function start(): void;

    /**
     * @throws \RuntimeException
     */
    public function quit(): void;

    public function isStarted(): bool;
}
