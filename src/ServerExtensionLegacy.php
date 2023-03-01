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

/**
 * @internal
 */
class ServerExtensionLegacy
{
    use ServerTrait;

    private static bool $enabled = false;

    /** @var Client[] */
    private static array $registeredClients = [];

    public static function registerClient(Client $client): void
    {
        if (self::$enabled && !\in_array($client, self::$registeredClients, true)) {
            self::$registeredClients[] = $client;
        }
    }

    public function executeBeforeFirstTest(): void
    {
        self::$enabled = true;
        $this->keepServerOnTeardown();
    }

    public function executeBeforeTest(string $test): void
    {
        self::reset();
    }

    public function executeAfterTest(string $test, float $time): void
    {
        self::reset();
    }

    public function executeAfterLastTest(): void
    {
        $this->stopWebServer();
    }

    public function executeAfterTestError(string $test, string $message, float $time): void
    {
        $this->pause(sprintf('Error: %s', $message));
    }

    public function executeAfterTestFailure(string $test, string $message, float $time): void
    {
        $this->pause(sprintf('Failure: %s', $message));
    }

    private static function reset(): void
    {
        self::$registeredClients = [];
    }

    public static function takeScreenshots(string $type, string $test): void
    {
        if (!self::$enabled || !($_SERVER['PANTHER_ERROR_SCREENSHOT_DIR'] ?? false)) {
            return;
        }

        foreach (self::$registeredClients as $i => $client) {
            $screenshotPath = sprintf('%s/%s_%s_%s-%d.png',
                $_SERVER['PANTHER_ERROR_SCREENSHOT_DIR'],
                date('Y-m-d_H-i-s'),
                $type,
                strtr($test, ['\\' => '-', ':' => '_']),
                $i
            );
            $client->takeScreenshot($screenshotPath);
            if ($_SERVER['PANTHER_ERROR_SCREENSHOT_ATTACH'] ?? false) {
                printf('[[ATTACHMENT|%s]]', $screenshotPath);
            }
        }
    }
}
