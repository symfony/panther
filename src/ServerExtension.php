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

namespace Symfony\Component\Panther;

use PHPUnit\Event\Test\Errored;
use PHPUnit\Event\Test\ErroredSubscriber;
use PHPUnit\Event\Test\Failed;
use PHPUnit\Event\Test\FailedSubscriber;
use PHPUnit\Event\Test\Finished as TestFinishedEvent;
use PHPUnit\Event\Test\FinishedSubscriber as TestFinishedSubscriber;
use PHPUnit\Event\Test\PreparationStarted as TestStartedEvent;
use PHPUnit\Event\Test\PreparationStartedSubscriber as TestStartedSubscriber;
use PHPUnit\Event\TestRunner\Finished as TestRunnerFinishedEvent;
use PHPUnit\Event\TestRunner\FinishedSubscriber as TestRunnerFinishedSubscriber;
use PHPUnit\Event\TestRunner\Started as TestRunnerStartedEvent;
use PHPUnit\Event\TestRunner\StartedSubscriber as TestRunnerStartedSubscriber;
use PHPUnit\Runner\AfterLastTestHook;
use PHPUnit\Runner\AfterTestErrorHook;
use PHPUnit\Runner\AfterTestFailureHook;
use PHPUnit\Runner\AfterTestHook;
use PHPUnit\Runner\BeforeFirstTestHook;
use PHPUnit\Runner\BeforeTestHook;
use PHPUnit\Runner\Extension\Extension;
use PHPUnit\Runner\Extension\Facade;
use PHPUnit\Runner\Extension\ParameterCollection;
use PHPUnit\TextUI\Configuration\Configuration;

/*
 *  @author Dany Maillard <danymaillard93b@gmail.com>
 */
if (interface_exists(Extension::class)) {
    /**
     * PHPUnit >= 10.
     */
    final class ServerExtension implements Extension
    {
        public function bootstrap(Configuration $configuration, Facade $facade, ParameterCollection $parameters): void
        {
            $extension = new ServerExtensionLegacy();

            $facade->registerSubscriber(new class($extension) implements TestRunnerStartedSubscriber {
                public function __construct(private $extension)
                {
                }

                public function notify(TestRunnerStartedEvent $event): void
                {
                    $this->extension->executeBeforeFirstTest();
                }
            });

            $facade->registerSubscriber(new class($extension) implements TestRunnerFinishedSubscriber {
                public function __construct(private $extension)
                {
                }

                public function notify(TestRunnerFinishedEvent $event): void
                {
                    $this->extension->executeAfterLastTest();
                }
            });

            $facade->registerSubscriber(new class($extension) implements TestStartedSubscriber {
                public function __construct(private $extension)
                {
                }

                public function notify(TestStartedEvent $event): void
                {
                    $this->extension->executeBeforeTest();
                }
            });

            $facade->registerSubscriber(new class($extension) implements TestFinishedSubscriber {
                public function __construct(private $extension)
                {
                }

                public function notify(TestFinishedEvent $event): void
                {
                    $this->extension->executeAfterTest();
                }
            });

            $facade->registerSubscriber(new class($extension) implements ErroredSubscriber {
                public function __construct(private $extension)
                {
                }

                public function notify(Errored $event): void
                {
                    $this->extension->executeAfterTestError();
                }
            });

            $facade->registerSubscriber(new class($extension) implements FailedSubscriber {
                public function __construct(private $extension)
                {
                }

                public function notify(Failed $event): void
                {
                    $this->extension->executeAfterTestFailure();
                }
            });
        }

        public static function registerClient(Client $client): void
        {
            ServerExtensionLegacy::registerClient($client);
        }
    }
} elseif (interface_exists(BeforeFirstTestHook::class)) {
    /**
     * PHPUnit < 10.
     */
    final class ServerExtension extends ServerExtensionLegacy implements BeforeFirstTestHook, BeforeTestHook, AfterTestHook, AfterLastTestHook, AfterTestErrorHook, AfterTestFailureHook
    {
    }
} else {
    exit("Failed to initialize Symfony\Component\Panther\ServerExtension, undetectable or unsupported phpunit version.");
}
