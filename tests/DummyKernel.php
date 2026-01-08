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

namespace Symfony\Component\Panther\Tests;

use Symfony\Bundle\FrameworkBundle\FrameworkBundle;
use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Bundle\BundleInterface;
use Symfony\Component\HttpKernel\KernelInterface;

/**
 * @author Kévin Dunglas <kevin@dunglas.dev>
 */
class DummyKernel implements KernelInterface
{
    public function handle(Request $request, $type = self::MAIN_REQUEST, $catch = true): Response
    {
        return new Response();
    }

    public function registerBundles(): iterable
    {
        return [];
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
        return [];
    }

    public function getBundle($name, $first = true): BundleInterface
    {
        return new FrameworkBundle();
    }

    public function locateResource($name, $dir = null, $first = true): string
    {
        return '';
    }

    public function getName(): string
    {
        return '';
    }

    public function getEnvironment(): string
    {
        return '';
    }

    public function isDebug(): bool
    {
        return false;
    }

    public function getRootDir(): string
    {
        return '';
    }

    public function getContainer(): ContainerInterface
    {
        return new class implements ContainerInterface {
            public function get($id, $invalidBehavior = ContainerInterface::EXCEPTION_ON_INVALID_REFERENCE): ?object
            {
                return new \stdClass();
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
        return 0;
    }

    public function getCacheDir(): string
    {
        return '';
    }

    public function getLogDir(): string
    {
        return '';
    }

    public function getCharset(): string
    {
        return '';
    }

    public function getProjectDir(): string
    {
        return '';
    }

    public function getBuildDir(): string
    {
        return '';
    }

    public function getShareDir(): ?string
    {
        return null;
    }
}
