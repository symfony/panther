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
use PHPUnit\Framework\TestResult;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;

if (class_exists(WebTestCase::class)) {
    abstract class PantherTestCase extends WebTestCase
    {
        use WebTestAssertionsTrait;

        public const CHROME = 'chrome';
        public const FIREFOX = 'firefox';
        public const COVERAGE_DIRECTORY = __DIR__.'/../var/panther-coverage';

        public function run(TestResult $result = null): TestResult
        {
            $result = parent::run($result);

            if (!is_dir(self::COVERAGE_DIRECTORY)) {
                return $result;
            }

            if (!class_exists(Filesystem::class)) {
                throw new \LogicException('The Filesystem component is not installed. Try running "composer require --dev symfony/filesystem".');
            }

            if (!class_exists(Finder::class)) {
                throw new \LogicException('The Finder component is not installed. Try running "composer require --dev symfony/finder".');
            }

            /** @var SplFileInfo[] $files */
            $files = (new Finder())->in(self::COVERAGE_DIRECTORY)->files()->name('*.code_coverage');
            foreach ($files as $file) {
                $content = $file->getContents();
                $rawCodeCoverageData = unserialize($content);

                if (!empty($content)) {
                    $result->getCodeCoverage()->append($rawCodeCoverageData, $this);
                }
            }

            (new Filesystem())->remove(self::COVERAGE_DIRECTORY);

            return $result;
        }

        protected function tearDown(): void
        {
            $this->doTearDown();
        }

        private function doTearDown(): void
        {
            parent::tearDown();
            $this->takeScreenshotIfTestFailed();
            self::getClient(null);
        }
    }
} else {
    // Without Symfony
    abstract class PantherTestCase extends TestCase
    {
        use PantherTestCaseTrait;

        public const CHROME = 'chrome';
        public const FIREFOX = 'firefox';

        protected function tearDown(): void
        {
            parent::tearDown();
            $this->takeScreenshotIfTestFailed();
        }
    }
}
