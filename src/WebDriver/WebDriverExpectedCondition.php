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
use Facebook\WebDriver\WebDriverExpectedCondition as BaseWebDriverExpectedCondition;

final class WebDriverExpectedCondition extends BaseWebDriverExpectedCondition
{
    /**
     * An expectation for checking if the given text is not present in the specified element.
     *
     * @param WebDriverBy $by   the locator used to find the element
     * @param string      $text the text to be presented in the element
     *
     * @return static condition returns whether the partial text is present in the element
     */
    public static function elementTextNotContains(WebDriverBy $by, string $text)
    {
        return new static(
            function (WebDriver $driver) use ($by, $text) {
                try {
                    $element_text = $driver->findElement($by)->getText();

                    return false === mb_strpos($element_text, $text);
                } catch (StaleElementReferenceException $e) {
                    return null;
                }
            }
        );
    }
}
