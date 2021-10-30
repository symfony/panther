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

use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Bundle\BundleInterface;
use Symfony\Component\HttpKernel\KernelInterface;

/**
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
class DummyKernel implements KernelInterface
{
    public function handle(Request $request, $type = self::MASTER_REQUEST, $catch = true): Response
    {
    }

    public function registerBundles(): iterable
    {
    }

    public function registerContainerConfiguration(LoaderInterface $loader): void
    {
    }

    public function boot(): void
    {
    }

    public function shutdown(): void
    {
    }

    public function getBundles(): array
    {
    }

    public function getBundle($name, $first = true): BundleInterface
    {
    }

    public function locateResource($name, $dir = null, $first = true): string
    {
    }

    public function getName(): string
    {
    }

    public function getEnvironment(): string
    {
    }

    public function isDebug(): bool
    {
    }

    public function getRootDir(): string
    {
    }

    public function getContainer(): ContainerInterface
    {
        return new class() implements ContainerInterface {
            public function get($id, $invalidBehavior = ContainerInterface::EXCEPTION_ON_INVALID_REFERENCE): ?object
            {
            }

            public function has($id): bool
            {
                return false;
            }

            public function set($id, $service): void
            {
            }

            public function initialized($id): bool
            {
                return true;
            }

            public function getParameter($name): ?string
            {
                return null;
            }

            public function hasParameter($name): bool
            {
                return false;
            }

            public function setParameter($name, $value): void
            {
            }
        };
    }

    public function getStartTime(): float
    {
    }

    public function getCacheDir(): string
    {
    }

    public function getLogDir(): string
    {
    }

    public function getCharset(): string
    {
    }

    public function getProjectDir(): string
    {
    }
}
