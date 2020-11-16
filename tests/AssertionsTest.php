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

    public function testDomCrawlerAssertions(): void
    {
        self::createPantherClient()->request('GET', '/basic.html');
        self::assertSelectorExists('.p-1');
        self::assertSelectorNotExists('#notexist');
        self::assertSelectorTextContains('body', 'P1');
        self::assertSelectorTextSame('.p-1', 'P1');
        self::assertSelectorTextNotContains('.p-1', 'not contained');
        self::assertPageTitleSame('A basic page');
        self::assertPageTitleContains('A basic');
        self::assertInputValueNotSame('in', '');
        self::assertInputValueSame('in', 'test');
        self::assertSelectorIsVisible('.p-1');
        self::assertSelectorIsEnabled('[name="in"]');
        self::createPantherClient()->request('GET', '/input-disabled.html');
        self::assertSelectorIsDisabled('[name="in-disabled"]');
        self::createPantherClient()->request('GET', '/text-hidden.html');
        self::assertSelectorIsNotVisible('.p-hidden');
    }

    public function testAssertionsWorkEvenWhenTheClientIsNotFresh(): void
    {
        self::createPantherClient()->request('GET', '/basic.html');
        self::assertSelectorExists('.p-1');
    }
}
