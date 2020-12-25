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

namespace Symfony\Component\Panther\Tests\WebDriver;

use Symfony\Component\Panther\Tests\TestCase;

/**
 * @author Dany Maillard <danymaillard93b@gmail.com>
 */
class WebDriverMouseTest extends TestCase
{
    public static function setUpBeforeClass(): void
    {
        self::createPantherClient()->request('GET', self::$baseUri.'/mouse.html');
    }

    /**
     * @dataProvider provide
     */
    public function test(string $method, string $cssSelector, string $result)
    {
        $client = self::createPantherClient();

        $client->getMouse()->{$method}($cssSelector);
        $this->assertEquals($result, $client->getCrawler()->filter('#result')->text());
    }

    public function provide(): iterable
    {
        yield ['clickTo', '#mouse', 'click'];
        // This error looks related to Chromedriver
        //yield ['doubleClickTo', '#mouse', 'dblclick'];
        yield ['contextClickTo', '#mouse', 'contextmenu'];
        yield ['mouseDownTo', '#mouse', 'mousedown'];
        yield ['mouseMoveTo', '#mouse', 'mousemove'];
        yield ['mouseUpTo', '#mouse-up', 'mouseup'];
    }
}
