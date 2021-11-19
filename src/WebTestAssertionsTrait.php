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
use Symfony\Component\DependencyInjection\Exception\ServiceNotFoundException;
use Symfony\Component\Panther\Client as PantherClient;

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
        $client = self::getClient();

        if ($client instanceof PantherClient) {
            $element = self::findElement($selector);
            self::assertNotNull($element, $message);

            return;
        }

        self::assertNotEmpty($client->getCrawler()->filter($selector));
    }

    /** @TODO replace this after patching Symfony to allow xpath selectors */
    public static function assertSelectorNotExists(string $selector, string $message = ''): void
    {
        $client = self::getClient();

        if ($client instanceof PantherClient) {
            $by = $client::createWebDriverByFromLocator($selector);
            $elements = $client->findElements($by);
            self::assertEmpty($elements, $message);

            return;
        }

        self::assertEmpty($client->getCrawler()->filter($selector));
    }

    /** @TODO replace this after patching Symfony to allow xpath selectors */
    public static function assertSelectorTextContains(string $selector, string $text, string $message = ''): void
    {
        self::assertStringContainsString($text, self::getText($selector), $message);
    }

    /** @TODO replace this after patching Symfony to allow xpath selectors */
    public static function assertSelectorTextNotContains(string $selector, string $text, string $message = ''): void
    {
        self::assertStringNotContainsString($text, self::getText($selector), $message);
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

    public static function assertSelectorWillExist(string $locator): void
    {
        /** @var PantherClient $client */
        $client = self::getClient();
        $client->waitFor($locator);
        self::assertSelectorExists($locator);
    }

    public static function assertSelectorWillNotExist(string $locator): void
    {
        /** @var PantherClient $client */
        $client = self::getClient();
        $client->waitForStaleness($locator);
        self::assertSelectorNotExists($locator);
    }

    public static function assertSelectorIsVisible(string $locator): void
    {
        $element = self::findElement($locator);
        self::assertTrue($element->isDisplayed(), 'Failed asserting that element is visible.');
    }

    public static function assertSelectorWillBeVisible(string $locator): void
    {
        /** @var PantherClient $client */
        $client = self::getClient();
        $client->waitForVisibility($locator);
        self::assertSelectorIsVisible($locator);
    }

    public static function assertSelectorIsNotVisible(string $locator): void
    {
        $element = self::findElement($locator);
        self::assertFalse($element->isDisplayed(), 'Failed asserting that element is not visible.');
    }

    public static function assertSelectorWillNotBeVisible(string $locator): void
    {
        /** @var PantherClient $client */
        $client = self::getClient();
        $client->waitForInvisibility($locator);
        self::assertSelectorIsNotVisible($locator);
    }

    public static function assertSelectorWillContain(string $locator, string $text): void
    {
        /** @var PantherClient $client */
        $client = self::getClient();
        $client->waitForElementToContain($locator, $text);
        self::assertSelectorTextContains($locator, $text);
    }

    public static function assertSelectorWillNotContain(string $locator, string $text): void
    {
        /** @var PantherClient $client */
        $client = self::getClient();
        $client->waitForElementToNotContain($locator, $text);
        self::assertSelectorTextNotContains($locator, $text);
    }

    public static function assertSelectorIsEnabled(string $locator): void
    {
        $element = self::findElement($locator);
        self::assertTrue($element->isEnabled(), 'Failed asserting that element is enabled.');
    }

    public static function assertSelectorWillBeEnabled(string $locator): void
    {
        /** @var PantherClient $client */
        $client = self::getClient();
        $client->waitForEnabled($locator);
        self::assertSelectorAttributeContains($locator, 'disabled');
    }

    public static function assertSelectorIsDisabled(string $locator): void
    {
        $element = self::findElement($locator);
        self::assertFalse($element->isEnabled(), 'Failed asserting that element is disabled.');
    }

    public static function assertSelectorWillBeDisabled(string $locator): void
    {
        /** @var PantherClient $client */
        $client = self::getClient();
        $client->waitForDisabled($locator);
        self::assertSelectorAttributeContains($locator, 'disabled', 'true');
    }

    public static function assertSelectorAttributeContains(string $locator, string $attribute, string $text = null): void
    {
        if (null === $text) {
            self::assertNull(self::getAttribute($locator, $attribute));

            return;
        }

        self::assertStringContainsString($text, self::getAttribute($locator, $attribute));
    }

    public static function assertSelectorAttributeWillContain(string $locator, string $attribute, string $text): void
    {
        /** @var PantherClient $client */
        $client = self::getClient();
        $client->waitForAttributeToContain($locator, $attribute, $text);
        self::assertSelectorAttributeContains($locator, $attribute, $text);
    }

    public static function assertSelectorAttributeNotContains(string $locator, string $attribute, string $text): void
    {
        self::assertStringNotContainsString($text, self::getAttribute($locator, $attribute));
    }

    public static function assertSelectorAttributeWillNotContain(string $locator, string $attribute, string $text): void
    {
        /** @var PantherClient $client */
        $client = self::getClient();
        $client->waitForAttributeToNotContain($locator, $attribute, $text);
        self::assertSelectorAttributeNotContains($locator, $attribute, $text);
    }

    /**
     * @internal
     */
    private static function getText(string $locator): string
    {
        $client = self::getClient();
        if ($client instanceof PantherClient) {
            return self::findElement($locator)->getText();
        }

        return $client->getCrawler()->filter($locator)->text(null, true);
    }

    /**
     * @internal
     */
    private static function getAttribute(string $locator, string $attribute): ?string
    {
        $client = self::getClient();
        if ($client instanceof PantherClient) {
            return self::findElement($locator)->getAttribute($attribute);
        }

        return $client->getCrawler()->filter($locator)->attr($attribute);
    }

    /**
     * @internal
     */
    private static function findElement(string $locator): WebDriverElement
    {
        $client = self::getClient();
        if (!$client instanceof PantherClient) {
            throw new \LogicException(sprintf('Using a client that is not an instance of "%s" is not supported.', PantherClient::class));
        }

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
     * @return KernelBrowser A browser instance
     */
    protected static function createClient(array $options = [], array $server = []): KernelBrowser
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

        /** @var KernelBrowser $wrapperClient */
        $wrapperClient = self::getClient($client);

        return $wrapperClient;
    }
}
