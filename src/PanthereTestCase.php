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

use PHPUnit\Framework\TestCase;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

if (\class_exists(WebTestCase::class)) {
    abstract class PanthereTestCase extends WebTestCase
    {
        use PanthereTestCaseTrait;
    }
} else {
    abstract class PanthereTestCase extends TestCase
    {
        use PanthereTestCaseTrait;
    }
}
