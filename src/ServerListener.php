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

namespace Panthere;

use PHPUnit\Framework\TestListener;
use PHPUnit\Framework\TestListenerDefaultImplementation;
use PHPUnit\Framework\TestSuite;

class ServerListener implements TestListener
{
    use TestListenerDefaultImplementation;

    public function startTestSuite(TestSuite $suite): void
    {
        echo "Starting Panthere server for test suite {$suite->getName()}...\n";
        PanthereTestCase::stopServerOnTeardown();
        PanthereTestCase::startWebServer(
            getenv('PANTHERE_LISTENER_SERVER_DIR') ?: null,
            getenv('PANTHERE_LISTENER_HOSTNAME') ?: '127.0.0.1',
            getenv('PANTHERE_LISTENER_PORT') ?: 9000
        );
    }

    public function endTestSuite(TestSuite $suite): void
    {
        echo "\nShutting down Panthere server...\n";
        PanthereTestCase::stopWebServer();
    }
}
