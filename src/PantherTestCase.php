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

if (\class_exists('PHPUnit\Framework\TestCase')) {
    if (\class_exists('Symfony\Bundle\FrameworkBundle\Test\WebTestCase')) {
        if (trait_exists('Symfony\Bundle\FrameworkBundle\Test\WebTestAssertionsTrait')) {
            if (trait_exists('Symfony\Bundle\FrameworkBundle\Test\ForwardCompatTestTrait')) {
                // Symfony 4.3
                abstract class PantherTestCase extends \Symfony\Bundle\FrameworkBundle\Test\WebTestCase
                {
                    public const CHROME = 'chrome';
                    public const FIREFOX = 'firefox';

                    use \Symfony\Bundle\FrameworkBundle\Test\ForwardCompatTestTrait;
                    use WebTestAssertionsTrait;

                    private function doTearDown()
                    {
                        parent::tearDown();
                        self::getClient(null);
                    }
                }
            } else {
                // Symfony 5
                abstract class PantherTestCase extends \Symfony\Bundle\FrameworkBundle\Test\WebTestCase
                {
                    public const CHROME = 'chrome';
                    public const FIREFOX = 'firefox';

                    use WebTestAssertionsTrait;

                    protected function tearDown(): void
                    {
                        $this->doTearDown();
                    }

                    private function doTearDown(): void
                    {
                        parent::tearDown();
                        self::getClient(null);
                    }
                }
            }
        } else {
            // Symfony 4.3 and inferior
            abstract class PantherTestCase extends \Symfony\Bundle\FrameworkBundle\Test\WebTestCase
            {
                public const CHROME = 'chrome';
                public const FIREFOX = 'firefox';

                use PantherTestCaseTrait;
            }
        }
    } else {
        // Without Symfony with PHPUnit
        abstract class PantherTestCase extends \PHPUnit\Framework\TestCase
        {
            public const CHROME = 'chrome';
            public const FIREFOX = 'firefox';

            use PantherTestCaseTrait;
        }
    }
} else {
    // Without Symfony without PHPUnit
    abstract class PantherTestCase
    {
        public const CHROME = 'chrome';
        public const FIREFOX = 'firefox';

        use PantherTestCaseTrait;
    }
}
