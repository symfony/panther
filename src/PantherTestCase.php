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

use PHPUnit\Framework\TestCase;
use Symfony\Bundle\FrameworkBundle\Test\ForwardCompatTestTrait;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

if (\class_exists(WebTestCase::class)) {
    if (trait_exists('Symfony\Bundle\FrameworkBundle\Test\WebTestAssertionsTrait')) {
        if (trait_exists('Symfony\Bundle\FrameworkBundle\Test\ForwardCompatTestTrait')) {
            // Symfony 4.3
            abstract class PantherTestCase extends WebTestCase
            {
                use ForwardCompatTestTrait;
                use WebTestAssertionsTrait;

                private function doTearDown()
                {
                    parent::tearDown();
                    self::getClient(null);
                }
            }
        } else {
            // Symfony 5
            abstract class PantherTestCase extends WebTestCase
            {
                use WebTestAssertionsTrait;

                protected function tearDown(): void
                {
                    parent::tearDown();
                    self::getClient(null);
                }
            }
        }
    } else {
        // Symfony 4.3 and inferior
        abstract class PantherTestCase extends WebTestCase
        {
            use PantherTestCaseTrait;
        }
    }
} else {
    // Without Symfony
    abstract class PantherTestCase extends TestCase
    {
        use PantherTestCaseTrait;
    }
}
