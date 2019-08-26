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
        $this->assertSelectorExists('.p-1');
        $this->assertSelectorNotExists('#notexist');
        $this->assertSelectorTextContains('body', 'P1');
        $this->assertSelectorTextSame('.p-1', 'P1');
        $this->assertSelectorTextNotContains('.p-1', 'not contained');
        $this->assertPageTitleSame('A basic page');
        $this->assertPageTitleContains('A basic');
        $this->assertInputValueNotSame('in', '');
        $this->assertInputValueSame('in', 'test');
    }

    public function testAssertionsWorkEvenWhenTheClientIsNotFresh(): void
    {
        self::createPantherClient()->request('GET', '/basic.html');
        $this->assertSelectorExists('.p-1');
    }
}
