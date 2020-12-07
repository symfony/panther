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
 * @author Dany Maillard <danymaillard93b@gmail.com>
 *
 * @internal
 */
trait ServerTrait
{
    public $testing = false;

    private function keepServerOnTeardown(): void
    {
        PantherTestCase::$stopServerOnTeardown = false;
    }

    private function stopWebServer(): void
    {
        PantherTestCase::stopWebServer();
    }

    private function pause($message): void
    {
        if (PantherTestCase::isWebServerStarted()
            && \in_array('--debug', $_SERVER['argv'], true)
            && $_SERVER['PANTHER_NO_HEADLESS'] ?? false
        ) {
            echo "$message\n\nPress enter to continue...";
            if (!$this->testing) {
                fgets(\STDIN);
            }
        }
    }

    private function takeScreenshot(string $type, string $test): void
    {
        if (!($_SERVER['PANTHER_SCREENSHOT_DIR'] ?? false) || !$client = PantherTestCase::getPrimaryPantherClient()) {
            return;
        }

        $client->takeScreenshot(sprintf('%s_%s.png', $type, strtr($test, ['\\' => '-', ':' => '_'])));
    }
}
