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

namespace Symfony\Component\Panther\Tests\DomCrawler;

use Facebook\WebDriver\WebDriverElement;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\Panther\Client;
use Symfony\Component\Panther\Client as PantherClient;
use Symfony\Component\Panther\DomCrawler\Image;
use Symfony\Component\Panther\DomCrawler\Link;
use Symfony\Component\Panther\Exception\InvalidArgumentException;
use Symfony\Component\Panther\Tests\TestCase;

/**
 * @author Kévin Dunglas <kevin@dunglas.dev>
 */
class CrawlerTest extends TestCase
{
    public function testCreateCrawler(): void
    {
        $crawler = self::createPantherClient()->request('GET', self::$baseUri.'/basic.html');
        $this->assertInstanceOf(Crawler::class, $crawler);
        $this->assertInstanceOf(WebDriverElement::class, $crawler);
    }

    #[DataProvider('clientFactoryProvider')]
    /**
     * @dataProvider clientFactoryProvider
     */
    public function testGetUri(callable $clientFactory): void
    {
        $crawler = $this->request($clientFactory, '/basic.html');
        $this->assertSame(static::$baseUri.'/basic.html', $crawler->getUri());
    }

    #[DataProvider('clientFactoryProvider')]
    /**
     * @dataProvider clientFactoryProvider
     */
    public function testHtml(callable $clientFactory): void
    {
        $crawler = $this->request($clientFactory, '/basic.html');
        $this->assertStringContainsString('<title>A basic page</title>', $crawler->html());
    }

    #[DataProvider('clientFactoryProvider')]
    /**
     * @dataProvider clientFactoryProvider
     */
    public function testIterate(callable $clientFactory): void
    {
        $crawler = $this->request($clientFactory, '/basic.html');
        foreach ($crawler as $element) {
            $this->assertEquals('html', $element instanceof \DOMElement ? $element->tagName : $element->getTagName());
        }
    }

    #[DataProvider('clientFactoryProvider')]
    /**
     * @dataProvider clientFactoryProvider
     */
    public function testFilterXpath(callable $clientFactory): void
    {
        $crawler = $this->request($clientFactory, '/basic.html');
        $crawler->filterXPath('descendant-or-self::body/p')->each(function (Crawler $crawler, int $i) {
            switch ($i) {
                case 0:
                    $this->assertSame('P1', $crawler->text(null, true));
                    break;
                case 1:
                    $this->assertSame('P2', $crawler->text(null, true));
                    break;
                case 2:
                    $this->assertSame('36', $crawler->text(null, true));
                    break;
                default:
                    $this->fail(\sprintf('Unexpected index "%d".', $i));
            }
        });
    }

    #[DataProvider('clientFactoryProvider')]
    /**
     * @dataProvider clientFactoryProvider
     */
    public function testFilter(callable $clientFactory): void
    {
        $crawler = $this->request($clientFactory, '/basic.html');
        $this->assertCount(3, $crawler->filter('main > p'));

        $texts = [];
        $crawler->filter('main > p')->each(function (Crawler $crawler, int $i) use (&$texts) {
            $texts[$i] = $crawler->text(null, true);
        });
        $this->assertSame(['Sibling', 'Sibling 2', 'Sibling 3'], $texts);
        $this->assertSame('Sibling 2', $crawler->filter('main')->filter('#a-sibling')->text(null, true));
    }

    #[DataProvider('clientFactoryProvider')]
    /**
     * @dataProvider clientFactoryProvider
     */
    public function testReduce(callable $clientFactory): void
    {
        $crawler = $this->request($clientFactory, '/basic.html');
        $crawler->filter('main > p')->reduce(function (Crawler $crawler) {
            return 'a-sibling' === $crawler->attr('id');
        })->each(function (Crawler $crawler) {
            $this->assertSame('p', $crawler->nodeName());
        });
    }

    #[DataProvider('clientFactoryProvider')]
    /**
     * @dataProvider clientFactoryProvider
     */
    public function testEq(callable $clientFactory): void
    {
        $crawler = $this->request($clientFactory, '/basic.html');
        $this->assertSame('a-sibling', $crawler->filter('main > p')->eq(1)->attr('id'));
    }

    #[DataProvider('clientFactoryProvider')]
    /**
     * @dataProvider clientFactoryProvider
     */
    public function testFirst(callable $clientFactory): void
    {
        $crawler = $this->request($clientFactory, '/basic.html');
        $this->assertSame('Sibling', $crawler->filter('main > p')->first()->text(null, true));
    }

    #[DataProvider('clientFactoryProvider')]
    /**
     * @dataProvider clientFactoryProvider
     */
    public function testLast(callable $clientFactory): void
    {
        $crawler = $this->request($clientFactory, '/basic.html');
        $this->assertSame('Sibling 3', $crawler->filter('main > p')->last()->text(null, true));
    }

    #[DataProvider('clientFactoryProvider')]
    /**
     * @dataProvider clientFactoryProvider
     */
    public function testSiblings(callable $clientFactory): void
    {
        $crawler = $this->request($clientFactory, '/basic.html');

        $texts = [];
        $crawler->filter('main > p')->siblings()->each(function (Crawler $c, int $i) use (&$texts) {
            $texts[$i] = $c->text(null, true);
        });

        $this->assertSame(['Main', 'Sibling 2', 'Sibling 3'], $texts);
    }

    #[DataProvider('clientFactoryProvider')]
    /**
     * @dataProvider clientFactoryProvider
     */
    public function testMatches(callable $clientFactory): void
    {
        $crawler = $this->request($clientFactory, '/basic.html');
        $p = $crawler->filter('#a-sibling');

        $this->assertTrue($p->matches('#a-sibling'));
        $this->assertTrue($p->matches('p'));
        $this->assertTrue($p->matches('.foo'));
        $this->assertFalse($p->matches('#other-id'));
        $this->assertFalse($p->matches('div'));
        $this->assertFalse($p->matches('.bar'));
    }

    #[DataProvider('clientFactoryProvider')]
    /**
     * @dataProvider clientFactoryProvider
     */
    public function testClosest(callable $clientFactory): void
    {
        $crawler = $this->request($clientFactory, '/closest.html');

        $foo = $crawler->filter('#foo');

        $newFoo = $foo->closest('#foo');
        $this->assertNotNull($newFoo);
        $this->assertSame('newFoo ok', $newFoo->attr('class'));

        $lorem1 = $foo->closest('.lorem1');
        $this->assertNotNull($lorem1);
        $this->assertSame('lorem1 ok', $lorem1->attr('class'));

        $lorem2 = $foo->closest('.lorem2');
        $this->assertNotNull($lorem2);
        $this->assertSame('lorem2 ok', $lorem2->attr('class'));

        $lorem3 = $foo->closest('.lorem3');
        $this->assertNull($lorem3);

        $notFound = $foo->closest('.not-found');
        $this->assertNull($notFound);
    }

    #[DataProvider('clientFactoryProvider')]
    /**
     * @dataProvider clientFactoryProvider
     */
    public function testNextAll(callable $clientFactory): void
    {
        $crawler = $this->request($clientFactory, '/basic.html');

        $texts = [];
        $crawler->filter('main > p')->nextAll()->each(function (Crawler $c, int $i) use (&$texts) {
            $texts[$i] = $c->text(null, true);
        });

        $this->assertSame(['Sibling 2', 'Sibling 3'], $texts);
    }

    #[DataProvider('clientFactoryProvider')]
    /**
     * @dataProvider clientFactoryProvider
     */
    public function testPreviousAll(callable $clientFactory): void
    {
        $crawler = $this->request($clientFactory, '/basic.html');

        $texts = [];
        $crawler->filter('main > p')->previousAll()->each(function (Crawler $c, int $i) use (&$texts) {
            $texts[$i] = $c->text(null, true);
        });

        $this->assertSame(['Main'], $texts);
    }

    #[DataProvider('clientFactoryProvider')]
    /**
     * @dataProvider clientFactoryProvider
     */
    public function testChildren(callable $clientFactory): void
    {
        $crawler = $this->request($clientFactory, '/basic.html');

        $names = [];
        $crawler->filter('body')->children()->each(function (Crawler $c, int $i) use (&$names) {
            $names[$i] = $c->nodeName();
        });

        $this->assertSame(['h1', 'main', 'p', 'p', 'input', 'p', 'div'], $names);
    }

    #[DataProvider('clientFactoryProvider')]
    /**
     * @dataProvider clientFactoryProvider
     */
    public function testChildrenFilter($clientFactory): void
    {
        $crawler = $this->request($clientFactory, '/basic.html');

        $names = [];
        $crawler->filter('body')->children('p')->each(function (Crawler $c, int $i) use (&$names) {
            $names[$i] = $c->nodeName();
        });

        $this->assertSame(['p', 'p', 'p'], $names);
    }

    /**
     * @dataProvider clientFactoryProvider
     *
     * @group legacy
     */
    public function testParents(callable $clientFactory): void
    {
        $crawler = $this->request($clientFactory, '/basic.html');

        if (!method_exists($crawler, 'parents')) {
            $this->markTestSkipped('Dom Crawler on Symfony 6.0 does not have `parents()` method');
        }

        $names = [];
        $crawler->filter('main > h1')->parents()->each(function (Crawler $c, int $i) use (&$names) {
            $names[$i] = $c->nodeName();
        });

        $this->assertSame(['main', 'body', 'html'], $names);
    }

    #[DataProvider('clientFactoryProvider')]
    /**
     * @dataProvider clientFactoryProvider
     */
    public function testAncestors(callable $clientFactory): void
    {
        $crawler = $this->request($clientFactory, '/basic.html');

        $names = [];
        $crawler->filter('main > h1')->ancestors()->each(function (Crawler $c, int $i) use (&$names) {
            $names[$i] = $c->nodeName();
        });

        $this->assertSame(['main', 'body', 'html'], $names);
    }

    #[DataProvider('clientFactoryProvider')]
    /**
     * @dataProvider clientFactoryProvider
     */
    public function testExtract(callable $clientFactory): void
    {
        $crawler = $this->request($clientFactory, '/basic.html');

        $this->assertSame([['', 'Sibling'], ['foo', 'Sibling 2'], ['', 'Sibling 3']], $crawler->filter('main > p')->extract(['class', '_text']));

        // Uncomment when https://github.com/symfony/symfony/pull/26433 will be merged
        $this->assertSame([[], [], []], $crawler->filter('main > p')->extract([]));
    }

    #[DataProvider('clientFactoryProvider')]
    /**
     * @dataProvider clientFactoryProvider
     */
    public function testLink(callable $clientFactory, string $type): void
    {
        $crawler = $this->request($clientFactory, '/link.html');

        $links = $crawler->selectLink('E2');
        $this->assertCount(2, $links);

        $uris = [];
        foreach ($links->links() as $link) {
            $uris[] = $link->getUri();
        }
        $this->assertSame([self::$baseUri.'/basic.html#e2', self::$baseUri.'/basic.html#e22'], $uris);

        $link = $links->link();
        $this->assertInstanceOf(\Symfony\Component\DomCrawler\Link::class, $link);
        if (Client::class === $type) {
            $this->assertInstanceOf(Link::class, $link);
        }

        $this->assertSame('GET', $link->getMethod());
        $this->assertSame(self::$baseUri.'/basic.html#e2', $link->getUri());

        $link = $crawler->selectLink('API Platform')->link();
        $this->assertSame('https://api-platform.com/', $link->getUri());
    }

    #[DataProvider('clientFactoryProvider')]
    /**
     * @dataProvider clientFactoryProvider
     */
    public function testImage(callable $clientFactory, string $type): void
    {
        $crawler = $this->request($clientFactory, '/link.html');

        $images = $crawler->selectImage('API Platform');
        $this->assertCount(2, $images);

        $uris = [];
        foreach ($images->images() as $image) {
            $uris[] = $image->getUri();
        }
        $this->assertSame(['https://api-platform.com/logo-250x250.png', 'https://avatars0.githubusercontent.com/u/13420081'], $uris);

        $image = $images->image();
        $this->assertInstanceOf(\Symfony\Component\DomCrawler\Image::class, $image);
        if (Client::class === $type) {
            $this->assertInstanceOf(Image::class, $image);
        }

        $this->assertSame('GET', $image->getMethod());
        $this->assertSame('https://api-platform.com/logo-250x250.png', $image->getUri());
    }

    #[DataProvider('clientFactoryProvider')]
    /**
     * @dataProvider clientFactoryProvider
     */
    public function testTextDefault(callable $clientFactory): void
    {
        $crawler = $this->request($clientFactory, '/basic.html');
        $this->assertSame('default', $crawler->filter('header')->text('default'));
    }

    #[DataProvider('clientFactoryProvider')]
    /**
     * @dataProvider clientFactoryProvider
     */
    public function testHtmlDefault(callable $clientFactory): void
    {
        $crawler = $this->request($clientFactory, '/basic.html');
        $this->assertSame('default', $crawler->filter('header')->html('default'));
    }

    #[DataProvider('clientFactoryProvider')]
    /**
     * @dataProvider clientFactoryProvider
     */
    public function testEmptyHtml(callable $clientFactory): void
    {
        $crawler = $this->request($clientFactory, '/basic.html');
        $this->assertEmpty($crawler->filter('.empty')->html(''));
    }

    #[DataProvider('clientFactoryProvider')]
    /**
     * @dataProvider clientFactoryProvider
     */
    public function testEmptyHtmlWithoutDefault(callable $clientFactory): void
    {
        $crawler = $this->request($clientFactory, '/basic.html');
        $this->assertEmpty($crawler->filter('.empty')->html());
    }

    #[DataProvider('clientFactoryProvider')]
    /**
     * @dataProvider clientFactoryProvider
     */
    public function testNormalizeText(callable $clientFactory, string $clientClass): void
    {
        if (PantherClient::class !== $clientClass) {
            $this->markTestSkipped('Need https://github.com/symfony/symfony/pull/34151');
        }

        $crawler = $this->request($clientFactory, '/normalize.html');
        $this->assertSame('Foo Bar Baz', $crawler->filter('#normalize')->text(null, true));
    }

    public function testDoNotNormalizeText(): void
    {
        $this->expectException(InvalidArgumentException::class);

        self::createPantherClient()->request('GET', self::$baseUri.'/normalize.html')->filter('#normalize')->text(null, false);
    }
}
