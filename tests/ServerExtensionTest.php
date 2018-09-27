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

namespace Symfony\Component\Panther\Tests;

use Symfony\Component\Panther\PantherTestCase;
use Symfony\Component\Panther\ServerExtension;

class ServerExtensionTest extends TestCase
{
    public static function tearDownAfterClass()
    {
        PantherTestCase::$stopServerOnTeardown = true;
    }

    public function testStartAndStop(): void
    {
        $listener = new ServerExtension();

        $listener->executeBeforeFirstTest();
        static::assertFalse(PantherTestCase::$stopServerOnTeardown);

        $listener->executeAfterLastTest();
        static::assertNull(PantherTestCase::$webServerManager);
    }
}
