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
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\BrowserKit\AbstractBrowser;

class AssertionsTest extends TestCase
{
    protected function setUp(): void
    {
        try {
            if (AbstractBrowser::class !== (new \ReflectionMethod(WebTestCase::class, 'getClient'))->getReturnType()->getName()) {
                $this->markTestSkipped('Old version of WebTestCase');
            }
        } catch (\ReflectionException $e) {
            $this->markTestSkipped('Old version of WebTestCase');
        }
    }

    #[DataProvider('clientFactoryProvider')]
    /**
     * @dataProvider clientFactoryProvider
     */
    public function testDomCrawlerAssertions(callable $clientFactory): void
    {
        $this->request($clientFactory, '/basic.html');

        $this->assertSelectorExists('.p-1');
        $this->assertSelectorNotExists('#notexist');
        $this->assertSelectorTextContains('body', 'P1');
        $this->assertSelectorTextSame('.p-1', 'P1');
        $this->assertSelectorTextNotContains('.p-1', 'not contained');
        $this->assertPageTitleSame('A basic page');
        $this->assertPageTitleContains('A basic');
        $this->assertInputValueNotSame('in', '');
        $this->assertInputValueSame('in', 'test');
        $this->assertSelectorAttributeContains('.price', 'data-old-price', '42');
        $this->assertSelectorAttributeNotContains('.price', 'data-old-price', '36');
    }

    public function testPantherAssertions(): void
    {
        self::createPantherClient()->request('GET', '/basic.html');
        $this->assertSelectorIsVisible('.p-1');
        $this->assertSelectorIsEnabled('[name="in"]');

        self::createPantherClient()->request('GET', '/input-disabled.html');
        $this->assertSelectorIsDisabled('[name="in-disabled"]');
        self::createPantherClient()->request('GET', '/text-hidden.html');
        $this->assertSelectorIsNotVisible('.p-hidden');
    }

    public function testAssertionsWorkEvenWhenTheClientIsNotFresh(): void
    {
        self::createPantherClient()->request('GET', '/basic.html');
        $this->assertSelectorExists('.p-1');
    }
}
