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

use PHPUnit\Framework\Attributes\DataProvider;

class FutureAssertionsTest extends TestCase
{
    #[DataProvider('futureDataProvider')]
    /** @dataProvider futureDataProvider */
    public function testFutureExistenceAssertion(string $locator): void
    {
        $crawler = self::createPantherClient()->request('GET', '/waitfor.html');
        $this->assertSelectorWillExist($locator);
        $this->assertSame('Hello', $crawler->filter('#hello')->text(null, true));
    }

    #[DataProvider('futureDataProvider')]
    /** @dataProvider futureDataProvider */
    public function testFutureStalenessAssertion(string $locator): void
    {
        $crawler = self::createPantherClient()->request('GET', '/waitfor-staleness.html');
        $this->assertSelectorWillNotExist($locator);
        $this->assertCount(0, $crawler->filter('body')->children());
    }

    #[DataProvider('futureDataProvider')]
    /** @dataProvider futureDataProvider */
    public function testFutureVisibilityAssertion(string $locator): void
    {
        $crawler = self::createPantherClient()->request('GET', '/waitfor-element-to-be-visible.html');
        $this->assertSelectorWillBeVisible($locator);
        $this->assertSame('Hello', $crawler->filter('#hello')->text(null, true));
        $this->assertSelectorExists($locator);
    }

    #[DataProvider('futureDataProvider')]
    /** @dataProvider futureDataProvider */
    public function testFutureInvisibilityAssertion(string $locator): void
    {
        $crawler = self::createPantherClient()->request('GET', '/waitfor-element-to-be-invisible.html');
        $this->assertSelectorWillNotBeVisible($locator);
        $this->assertSame('', $crawler->filter('#hello')->text(null, true));
    }

    #[DataProvider('futureDataProvider')]
    /** @dataProvider futureDataProvider */
    public function testFutureContainAssertion(string $locator): void
    {
        $crawler = self::createPantherClient()->request('GET', '/waitfor-element-to-contain.html');
        $this->assertSelectorWillContain($locator, 'new content');
        $this->assertSame('Hello new content', $crawler->filter('#hello')->text(null, true));
    }

    #[DataProvider('futureDataProvider')]
    /** @dataProvider futureDataProvider */
    public function testFutureNotContainAssertion(string $locator): void
    {
        $crawler = self::createPantherClient()->request('GET', '/waitfor-element-to-not-contain.html');
        $this->assertSelectorWillNotContain($locator, 'removed content');
        $this->assertSame('Hello', $crawler->filter('#hello')->text(null, true));
    }

    #[DataProvider('futureDataProvider')]
    /** @dataProvider futureDataProvider */
    public function testFutureEnabledAssertion(string $locator): void
    {
        $crawler = self::createPantherClient()->request('GET', '/waitfor-input-to-be-enabled.html');
        $this->assertSelectorWillBeEnabled($locator);
        $this->assertNull($crawler->filter('#hello')->getAttribute('disabled'));
    }

    #[DataProvider('futureDataProvider')]
    /** @dataProvider futureDataProvider */
    public function testFutureDisabledAssertion(string $locator): void
    {
        $crawler = self::createPantherClient()->request('GET', '/waitfor-input-to-be-disabled.html');
        $this->assertSelectorWillBeDisabled($locator);
        $this->assertSame('true', $crawler->filter('#hello')->getAttribute('disabled'));
    }

    #[DataProvider('futureDataProvider')]
    /**
     * @dataProvider futureDataProvider
     */
    public function testFutureAttributeContainAssertion(string $locator): void
    {
        $crawler = self::createPantherClient()->request('GET', '/waitfor-attribute-to-contain.html');
        $this->assertSelectorAttributeWillContain($locator, 'data-old-price', '42');
        $this->assertSame('42', $crawler->filter('#hello')->getAttribute('data-old-price'));
    }

    #[DataProvider('futureDataProvider')]
    /**
     * @dataProvider futureDataProvider
     */
    public function testFutureAttributeNotContainAssertion(string $locator): void
    {
        $crawler = self::createPantherClient()->request('GET', '/waitfor-attribute-to-contain.html');
        $this->assertSelectorAttributeWillNotContain($locator, 'data-old-price', '36');
        $this->assertSame('42', $crawler->filter('#hello')->getAttribute('data-old-price'));
    }

    public static function futureDataProvider(): iterable
    {
        yield 'css selector' => ['locator' => '#hello'];
        yield 'xpath expression' => ['locator' => '//*[@id="hello"]'];
    }
}
