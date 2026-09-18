<?php

/*
 * This file is part of the Panther project.
 *
 * (c) Kévin Dunglas <kevin@dunglas.dev>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Symfony\Component\Panther\Tests\ProcessManager;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Panther\Exception\RuntimeException;
use Symfony\Component\Panther\ProcessManager\WebServerReadinessProbeTrait;
use Symfony\Component\Process\Process;

class WebServerReadinessProbeTraitTest extends TestCase
{
    #[DataProvider('provideTerminatedProcesses')]
    /**
     * @dataProvider provideTerminatedProcesses
     */
    public function testTerminatedProcess(bool $disableOutput, string $expectedMessage): void
    {
        $process = new Process([\PHP_BINARY, '-r', 'fwrite(STDERR, "Startup failed."); exit(1);']);
        if ($disableOutput) {
            $process->disableOutput();
        }
        $process->run();

        $probe = new class {
            use WebServerReadinessProbeTrait;
        };

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage($expectedMessage);

        $probe->waitUntilReady($process, 'http://127.0.0.1', 'web server');
    }

    public static function provideTerminatedProcesses(): iterable
    {
        yield 'disabled output' => [true, 'Could not start web server. Exit code: 1 (General error).'];
        yield 'enabled output' => [false, 'Could not start web server. Exit code: 1 (General error). Error output: Startup failed.'];
    }
}
