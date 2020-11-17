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

namespace Symfony\Component\Panther\WebDriver;

use Facebook\WebDriver\Exception\StaleElementReferenceException;
use Facebook\WebDriver\WebDriver;
use Facebook\WebDriver\WebDriverBy;

final class PantherWebDriverExpectedCondition
{
    public static function elementTextNotContains(WebDriverBy $by, string $text): callable
    {
        return static function (WebDriver $driver) use ($by, $text) {
            try {
                $element_text = $driver->findElement($by)->getText();

                return false === strpos($element_text, $text);
            } catch (StaleElementReferenceException $e) {
                return null;
            }
        }
        ;
    }
}
