# Panthère
**A browser testing and web scraping library for [PHP](https://php.net) and [Symfony](https://symfony.com)**

*Panthère* is a convenient standalone library to scrape websites and to run end to end tests **using real browsers**.

Because it leverages [the W3C's WebDriver protocol](https://www.w3.org/TR/webdriver/) to drive native web browsers such
as Google Chrome and Firefox, Panthère is super powerful.

Because it implements the popular Symfony's [BrowserKit](https://symfony.com/doc/current/components/browser_kit.html) and
[DomCrawler](https://symfony.com/doc/current/components/dom_crawler.html) APIs, Panthère is very easy to use, and contains
all features you need to test your apps. It will sound familiar if you ever created [a functional test for a Symfony app](https://symfony.com/doc/current/testing.html#functional-tests):
the API is exactly the same!
Keep in mind that Panthère doesn't depend of Symfony, it's a standalone library.

Because Panthère automatically finds your local installation of Chrome and launches it (thanks to [ChromeDriver](https://sites.google.com/a/chromium.org/chromedriver/)),
you don't have anything to install on your computer: no Selenium server nor obscure driver.
In test mode, Panthère automatically starts your application using [the PHP built-in web-server](http://php.net/manual/en/features.commandline.webserver.php).
Focus on writing your tests or web-scraping scenario, Panthère takes care of everything else.

## Install

Use [Composer](https://getcomposer.org/) to install Panthère in your project:

    composer req dunglas/panthere

## Basic Usage

```php
<?php

require __DIR__.'/vendor/autoload.php'; // Composer's autoloader

$client = \Panthere\Client::createChromeClient();
$crawler = $client->request('GET', 'http://api-platform.com'); // Yes, this website is 100% in JavaScript

$link = $crawler->selectLink('Support')->link();
$crawler = $client->click($link);

// Wait for an element to be rendered
$client->waitFor('.support');

echo $crawler->filter('.support')->text();
$client->takeScreenshot('screen.png'); // Yeah, screenshot!
```

## Testing Usage

The `PanthereTestCase` class allows you to easily write E2E tests. It automatically starts your app using the built-in PHP
web server and let you crawl it using Panthère.
It extends [PHPUnit](https://phpunit.de/)'s `TestCase` and provide all testing tools you're used to.

```php
<?php

use Panthere\PanthereTestCase;

class E2eTest extends PanthereTestCase
{
    public function testMyApp()
    {
        $client = static::createPanthereClient(); // Your app is automatically started using the built-in web server
        $crawler = $client->request('GET', static::$baseUri.'/mypage'); // static::$baseUri contains the base URL

        $this->assertContains('My Title', $crawler->filter('title')->text()); // You can use any PHPUnit assertion
    }
}
```

To run this test:

    phpunit tests/E2eTest.php

### A Polymorph Feline

If you are testing a Symfony application, `PanthereTestCase` automatically extends the `WebTestCase` class. It means that
you can easily create functional tests executing directly the kernel of your application and accessing all your existing
services. Unlike the Panthère's client, the Symfony's testing client doesn't support JavaScript or taking screenshots, but
it is super-fast!

Alternatively (and even for non-Symfony apps), Panthère can also leverage the [Goutte](https://github.com/FriendsOfPHP/Goutte)
web scraping library. Goutte is an intermediate between the Symfony's test client and the Panthère one: it sends real HTTP
requests, is fast and can browse any webpage, not only the ones of the application under test.
But, because it is entirely written in PHP, Goutte doesn't support JavaScript and other advanced features.

The fun part is that the 3 libraries implement the exact same API, so you can switch from one to another just by calling
the appropriate factory method, and find the good trade off for every single test case (do I need JavaScript, do I need
to authenticate to an external SSO server, do I want to access the kernel of the current request...).

```php
<?php

use Panthere\PanthereTestCase;

class E2eTest extends PanthereTestCase
{
    public function testMyApp()
    {
        $symfonyClient = static::createClient(); // A cute kitty: the Symfony's functional test too
        $goutteClient = static::createGoutteClient(); // An agile lynx: Goutte
        $panthereClient = static::createPanthereClient(); // A majestic Panther
        
        // Both Goutte and Panthère benefits from the built-in HTTP server
        
        // enjoy the same API for the 3 felines
        // $*client->request('GET', '...')

        $kernel = static::createKernel(); // You can also access to the app's kernel

        // ...
    }
}
```

## Features

Unlike testing and web scraping libraries you're used to, Panthère:

* executes the JavaScript code contained in webpages
* supports all everything that Chrome (or Firefox) implements
* can take screenshots
* can wait for the appearance of elements loaded asynchronously 
* lets you run your own JS code or XPath queries in the context of the loaded page
* supports custom [Selenium server](https://www.seleniumhq.org) installations
* supports remote browser testing services including [SauceLabs](https://saucelabs.com/) and [BrowserStack](https://www.browserstack.com/)

## Documentation

Because Panthère implements the API of popular, it already has an extensive documentation:

* For the `Client` class, read [the BrowserKit's documentation](https://symfony.com/doc/current/components/browser_kit.html)
* For the `Crawler` class, read [the DomCrawler's documentation](https://symfony.com/doc/current/components/dom_crawler.html)
* For Webdriver, read [the Facebook's PHP WebDriver documentation](https://github.com/facebook/php-webdriver)

## Travis CI Integration

Panthère will work out of the box with Travis if you add the Chrome addon. Here is a minimal `.travis.yml` file to run
Panthère tests:

```yaml
language: php
addons:
  chrome: stable

php:
  - 7.2

script:
  - phpunit
```

## Limitations

The following features are not currently supported:

* Crawling XML documents (only HTML is supported)
* Updating existing documents (browsers are mostly used to consume data, not to create webpages)
* Setting form values using the multidimensional PHP array syntax
* Methods returning an instance of `\DOMElement` (because this library uses `WebDriverElement` internally)
* Selecting invalid choices in select

Pull Requests are welcome to fill the remaining gaps!

## Credits

Created by [Kévin Dunglas](https://dunglas.fr). Sponsored by [Les-Tilleuls.coop](https://les-tilleuls.coop).
