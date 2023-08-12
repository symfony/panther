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

use SebastianBergmann\CodeCoverage\CodeCoverage;
use SebastianBergmann\CodeCoverage\Driver\Selector;
use SebastianBergmann\CodeCoverage\Filter;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\HttpKernelInterface;

trait PantherCoverageKernelTrait
{
    /**
     * {@inheritDoc}
     */
    public function handle(Request $request, int $type = HttpKernelInterface::MAIN_REQUEST, bool $catch = true): Response
    {
        if ('true' === getenv('PANTHER_COVERAGE') && 'panther' === $this->environment) {
            if (!class_exists(Filesystem::class)) {
                throw new \LogicException('The Filesystem component is not installed. Try running "composer require --dev symfony/filesystem".');
            }

            $filter = new Filter();
            $filter->includeDirectory(__DIR__.'/../src');

            $coverage = new CodeCoverage(
                (new Selector())->forLineCoverage($filter),
                $filter
            );

            $coverage->start(md5(uniqid((string) mt_rand(), true)));
        }

        $response = parent::handle($request, $type, $catch);

        if (true === isset($coverage)) {
            $data = $coverage->stop();

            $jsonCodeCoverageFile = md5(uniqid((string) mt_rand(), true)).'.code_coverage';

            (new Filesystem())->appendToFile(PantherTestCase::COVERAGE_DIRECTORY.'/'.$jsonCodeCoverageFile, serialize($data));
        }

        return $response;
    }
}
