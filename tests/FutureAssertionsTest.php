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

use Symfony\Component\Panther\DomCrawler\Crawler;

class FutureAssertionsTest extends TestCase
{
    /** @dataProvider futureDataProvider */
    public function testFutureExistenceAssertion(string $locator): void
    {
        self::createPantherClient()->request('GET', '/waitfor.html');
        $crawler = $this->assertSelectorWillExist($locator);
        $this->assertInstanceOf(Crawler::class, $crawler);
        $this->assertSame('Hello', $crawler->filter('#hello')->text());
    }

    /** @dataProvider futureDataProvider */
    public function testFutureStalenessAssertion(string $locator): void
    {
        self::createPantherClient()->request('GET', '/waitfor-staleness.html');
        $crawler = $this->assertSelectorWillNotExist($locator);
        $this->assertInstanceOf(Crawler::class, $crawler);
    }

    /** @dataProvider futureDataProvider */
    public function testFutureVisibilityAssertion(string $locator): void
    {
        self::createPantherClient()->request('GET', '/waitfor-element-to-be-visible.html');
        $crawler = $this->assertSelectorWillBeVisible($locator);
        $this->assertInstanceOf(Crawler::class, $crawler);
        $this->assertSame('Hello', $crawler->filter('#hello')->text());
        $this->assertSelectorExists($locator);
    }

    /** @dataProvider futureDataProvider */
    public function testFutureInvisibilityAssertion(string $locator): void
    {
        self::createPantherClient()->request('GET', '/waitfor-element-to-be-invisible.html');
        $crawler = $this->assertSelectorWillNotBeVisible($locator);
        $this->assertSame('', $crawler->filter('#hello')->text());
    }

    /** @dataProvider futureDataProvider */
    public function testFutureContainAssertion(string $locator): void
    {
        self::createPantherClient()->request('GET', '/waitfor-element-to-contain.html');
        $crawler = $this->assertSelectorWillContain($locator, 'new content');
        $this->assertSame('Hello new content', $crawler->filter('#hello')->text());
    }

    /** @dataProvider futureDataProvider */
    public function testFutureNotContainAssertion(string $locator): void
    {
        self::createPantherClient()->request('GET', '/waitfor-element-to-not-contain.html');
        $crawler = $this->assertSelectorWillNotContain($locator, 'removed content');
        $this->assertSame('Hello', $crawler->filter('#hello')->text());
    }

    /** @dataProvider futureDataProvider */
    public function testFutureEnabledAssertion(string $locator): void
    {
        self::createPantherClient()->request('GET', '/waitfor-input-to-be-enabled.html');
        $crawler = $this->assertSelectorWillBeEnabled($locator);
        $this->assertNull($crawler->filter('#hello')->getAttribute('disabled'));
    }

    /** @dataProvider futureDataProvider */
    public function testFutureDisabledAssertion(string $locator): void
    {
        self::createPantherClient()->request('GET', '/waitfor-input-to-be-disabled.html');
        $crawler = $this->assertSelectorWillBeDisabled($locator);
        $this->assertSame('true', $crawler->filter('#hello')->getAttribute('disabled'));
    }

    /**
     * @dataProvider futureDataProvider
     */
    public function testFutureAttributeContainAssertion(string $locator)
    {
        self::createPantherClient()->request('GET', '/waitfor-attribute-to-contain.html');
        $crawler = $this->assertSelectorAttributeWillContain($locator, 'data-old-price', '42');
        $this->assertSame('42', $crawler->filter('#hello')->getAttribute('data-old-price'));
    }

    /**
     * @dataProvider futureDataProvider
     */
    public function testFutureAttributeNotContainAssertion(string $locator)
    {
        self::createPantherClient()->request('GET', '/waitfor-attribute-to-contain.html');
        $crawler = $this->assertSelectorAttributeWillNotContain($locator, 'data-old-price', '36');
        $this->assertSame('42', $crawler->filter('#hello')->getAttribute('data-old-price'));
    }

    public function futureDataProvider(): iterable
    {
        yield 'css selector' => ['locator' => '#hello'];
        yield 'xpath expression' => ['locator' => '//*[@id="hello"]'];
    }
}
