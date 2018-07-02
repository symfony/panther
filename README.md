<h1 align="center"><img src="panthere.png" alt="Panthère"></h1>

**A browser testing and web scraping library for [PHP](https://php.net) and [Symfony](https://symfony.com)**

[![Build Status](https://travis-ci.org/symfony/panthere.svg?branch=master)](https://travis-ci.org/symfony/panthere)

*Panthère* is a convenient standalone library to scrape websites and to run end-to-end tests **using real browsers**.

Panthère is super powerful, it leverages [the W3C's WebDriver protocol](https://www.w3.org/TR/webdriver/) to drive native web browsers such as Google Chrome and Firefox.

Panthère is very easy to use, because it implements the popular Symfony's [BrowserKit](https://symfony.com/doc/current/components/browser_kit.html) and
[DomCrawler](https://symfony.com/doc/current/components/dom_crawler.html) APIs, and contains
all features you need to test your apps. It will sound familiar if you have ever created [a functional test for a Symfony app](https://symfony.com/doc/current/testing.html#functional-tests):
as the API is exactly the same!
Keep in mind that Panthère can be used in every PHP project, it's a standalone library.

Panthère automatically finds your local installation of Chrome and launches it (thanks to [ChromeDriver](https://sites.google.com/a/chromium.org/chromedriver/)),
so you don't need to install anything on your computer, neither Selenium server nor obscure driver.

In test mode, Panthère automatically starts your application using [the PHP built-in web-server](http://php.net/manual/en/features.commandline.webserver.php).
You can just focus on writing your tests or web-scraping scenario, Panthère takes care of everything else.

## Install

Use [Composer](https://getcomposer.org/) to install Panthère in your project. You may want to use the --dev flag if you want to use Panthere for testing only and not for web scraping:

    composer req symfony/panthere:dev-master
    
    composer req --dev symfony/panthere:dev-master

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
        $crawler = $client->request('GET', '/mypage');

        $this->assertContains('My Title', $crawler->filter('title')->text()); // You can use any PHPUnit assertion
    }
}
```

To run this test:

    phpunit tests/E2eTest.php

### A Polymorph Feline

If you are testing a Symfony application, `PanthereTestCase` automatically extends the `WebTestCase` class. It means
you can easily create functional tests, which can directly execute the kernel of your application and access all your existing
services. Unlike the Panthère's client, the Symfony's testing client doesn't support JavaScript and screenshots capturing, but
it is super-fast!

Alternatively (and even for non-Symfony apps), Panthère can also leverage the [Goutte](https://github.com/FriendsOfPHP/Goutte)
web scraping library, which is an intermediate between the Symfony's and the Panthère's test clients. Goutte sends real HTTP
requests, it is fast and is able to browse any webpage, not only the ones of the application under test.
But Goutte doesn't support JavaScript and other advanced features because it is entirely written in PHP.

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
* supports everything that Chrome (or Firefox) implements
* allows screenshots taking
* can wait for the appearance of elements loaded asynchronously 
* lets you run your own JS code or XPath queries in the context of the loaded page
* supports custom [Selenium server](https://www.seleniumhq.org) installations
* supports remote browser testing services including [SauceLabs](https://saucelabs.com/) and [BrowserStack](https://www.browserstack.com/)

## Documentation

Since Panthère implements the API of popular, it already has an extensive documentation:

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
