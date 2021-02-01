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

use Facebook\WebDriver\WebDriverElement;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestAssertionsTrait as BaseWebTestAssertionsTrait;
use Symfony\Component\BrowserKit\AbstractBrowser;
use Symfony\Component\DependencyInjection\Exception\ServiceNotFoundException;
use Symfony\Component\Panther\Client as PantherClient;
use Symfony\Component\Panther\DomCrawler\Crawler;

/**
 * Tweaks Symfony's WebTestAssertionsTrait to be compatible with Panther.
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
trait WebTestAssertionsTrait
{
    use BaseWebTestAssertionsTrait {
        assertPageTitleSame as private baseAssertPageTitleSame;
        assertPageTitleContains as private baseAssertPageTitleContains;
    }
    use PantherTestCaseTrait;

    /** @TODO replace this after patching Symfony to allow xpath selectors */
    public static function assertSelectorExists(string $selector, string $message = ''): void
    {
        $element = self::findElement($selector);
        self::assertNotNull($element, $message);
    }

    /** @TODO replace this after patching Symfony to allow xpath selectors */
    public static function assertSelectorNotExists(string $selector, string $message = ''): void
    {
        /** @var PantherClient $client */
        $client = self::getClient();
        $by = $client::createWebDriverByFromLocator($selector);
        $elements = $client->findElements($by);
        self::assertEmpty($elements, $message);
    }

    /** @TODO replace this after patching Symfony to allow xpath selectors */
    public static function assertSelectorTextContains(string $selector, string $text, string $message = ''): void
    {
        $element = self::findElement($selector);
        self::assertStringContainsString($text, $element->getText(), $message);
    }

    /** @TODO replace this after patching Symfony to allow xpath selectors */
    public static function assertSelectorTextNotContains(string $selector, string $text, string $message = ''): void
    {
        $element = self::findElement($selector);
        self::assertStringNotContainsString($text, $element->getText(), $message);
    }

    public static function assertPageTitleSame(string $expectedTitle, string $message = ''): void
    {
        $client = self::getClient();
        if ($client instanceof PantherClient) {
            self::assertSame($expectedTitle, $client->getTitle());

            return;
        }

        self::baseAssertPageTitleSame($expectedTitle, $message);
    }

    public static function assertPageTitleContains(string $expectedTitle, string $message = ''): void
    {
        $client = self::getClient();
        if ($client instanceof PantherClient) {
            if (method_exists(self::class, 'assertStringContainsString')) {
                self::assertStringContainsString($expectedTitle, $client->getTitle());

                return;
            }

            self::assertStringContainsString($expectedTitle, $client->getTitle());

            return;
        }

        self::baseAssertPageTitleContains($expectedTitle, $message);
    }

    public static function assertSelectorWillExist(string $locator): Crawler
    {
        /** @var PantherClient $client */
        $client = self::getClient();
        $crawler = $client->waitFor($locator);
        self::assertSelectorExists($locator);

        return $crawler;
    }

    public static function assertSelectorWillNotExist(string $locator): Crawler
    {
        /** @var PantherClient $client */
        $client = self::getClient();
        $crawler = $client->waitForStaleness($locator);
        self::assertSelectorNotExists($locator);

        return $crawler;
    }

    public static function assertSelectorIsVisible(string $locator): void
    {
        $element = self::findElement($locator);
        self::assertTrue($element->isDisplayed(), 'Failed asserting that element is visible.');
    }

    public static function assertSelectorWillBeVisible(string $locator): Crawler
    {
        /** @var PantherClient $client */
        $client = self::getClient();
        $crawler = $client->waitForVisibility($locator);
        self::assertSelectorIsVisible($locator);

        return $crawler;
    }

    public static function assertSelectorIsNotVisible(string $locator): void
    {
        $element = self::findElement($locator);
        self::assertFalse($element->isDisplayed(), 'Failed asserting that element is not visible.');
    }

    public static function assertSelectorWillNotBeVisible(string $locator): Crawler
    {
        /** @var PantherClient $client */
        $client = self::getClient();
        $crawler = $client->waitForInvisibility($locator);
        self::assertSelectorIsNotVisible($locator);

        return $crawler;
    }

    public static function assertSelectorWillContain(string $locator, string $text): Crawler
    {
        /** @var PantherClient $client */
        $client = self::getClient();
        $crawler = $client->waitForElementToContain($locator, $text);
        self::assertSelectorTextContains($locator, $text);

        return $crawler;
    }

    public static function assertSelectorWillNotContain(string $locator, string $text): Crawler
    {
        /** @var PantherClient $client */
        $client = self::getClient();
        $crawler = $client->waitForElementToNotContain($locator, $text);
        self::assertSelectorTextNotContains($locator, $text);

        return $crawler;
    }

    public static function assertSelectorIsEnabled(string $locator): void
    {
        $element = self::findElement($locator);
        self::assertTrue($element->isEnabled(), 'Failed asserting that element is enabled.');
    }

    public static function assertSelectorWillBeEnabled(string $locator): Crawler
    {
        /** @var PantherClient $client */
        $client = self::getClient();
        $crawler = $client->waitForEnabled($locator);
        self::assertSelectorAttributeToContain($locator, 'disabled');

        return $crawler;
    }

    public static function assertSelectorIsDisabled(string $locator): void
    {
        $element = self::findElement($locator);
        self::assertFalse($element->isEnabled(), 'Failed asserting that element is disabled.');
    }

    public static function assertSelectorWillBeDisabled(string $locator): Crawler
    {
        /** @var PantherClient $client */
        $client = self::getClient();
        $crawler = $client->waitForDisabled($locator);
        self::assertSelectorAttributeToContain($locator, 'disabled', 'true');

        return $crawler;
    }

    public static function assertSelectorAttributeToContain(string $locator, string $attribute, string $text = null): void
    {
        $element = self::findElement($locator);

        if (null === $text) {
            self::assertNull($element->getAttribute($attribute));

            return;
        }

        self::assertStringContainsString($text, $element->getAttribute($attribute));
    }

    public static function assertSelectorAttributeWillContain(string $locator, string $attribute, string $text): Crawler
    {
        /** @var PantherClient $client */
        $client = self::getClient();
        $crawler = $client->waitForAttributeToContain($locator, $attribute, $text);
        self::assertSelectorAttributeToContain($locator, $attribute, $text);

        return $crawler;
    }

    public static function assertSelectorAttributeToNotContain(string $locator, string $attribute, string $text): void
    {
        $element = self::findElement($locator);
        self::assertStringNotContainsString($text, $element->getAttribute($attribute));
    }

    public static function assertSelectorAttributeWillNotContain(string $locator, string $attribute, string $text): Crawler
    {
        /** @var PantherClient $client */
        $client = self::getClient();
        $crawler = $client->waitForAttributeToNotContain($locator, $attribute, $text);
        self::assertSelectorAttributeToNotContain($locator, $attribute, $text);

        return $crawler;
    }

    private static function findElement(string $locator): WebDriverElement
    {
        /** @var PantherClient $client */
        /** @var PantherClient $client */
        $client = self::getClient();
        $by = $client::createWebDriverByFromLocator($locator);

        return $client->findElement($by);
    }

    // Copied from WebTestCase to allow assertions to work with createClient

    /**
     * Creates a KernelBrowser.
     *
     * @param array $options An array of options to pass to the createKernel method
     * @param array $server  An array of server parameters
     *
     * @return AbstractBrowser A browser instance
     */
    protected static function createClient(array $options = [], array $server = [])
    {
        $kernel = static::bootKernel($options);

        try {
            /** @var KernelBrowser $client */
            $client = $kernel->getContainer()->get('test.client');
        } catch (ServiceNotFoundException $e) {
            if (class_exists(KernelBrowser::class)) {
                throw new \LogicException('You cannot create the client used in functional tests if the "framework.test" config is not set to true.');
            }
            throw new \LogicException('You cannot create the client used in functional tests if the BrowserKit component is not available. Try running "composer require symfony/browser-kit"');
        }

        $client->setServerParameters($server);

        return self::getClient($client);
    }
}
