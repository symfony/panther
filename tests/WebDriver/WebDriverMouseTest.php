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

namespace Symfony\Component\Panther\Tests\WebDriver;

use PHPUnit\Framework\Attributes\DataProvider;
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

    #[DataProvider('provide')]
    /**
     * @dataProvider provide
     */
    public function test(string $method, string $cssSelector, string $result): void
    {
        $client = self::createPantherClient();

        $client->getMouse()->{$method}($cssSelector);
        $this->assertEquals($result, $client->getCrawler()->filter('#result')->text(null, true));
    }

    public static function provide(): iterable
    {
        yield ['clickTo', '#mouse', 'click'];
        // Double clicks aren't detected as dblclick events anymore in W3C mode, looks related to https://github.com/w3c/webdriver/issues/1197
        // yield ['doubleClickTo', '#mouse', 'dblclick'];
        yield ['contextClickTo', '#mouse', 'contextmenu'];
        yield ['mouseDownTo', '#mouse', 'mousedown'];
        yield ['mouseMoveTo', '#mouse', 'mousemove'];
        yield ['mouseUpTo', '#mouse-up', 'mouseup'];
    }
}
