<h1 align="center"><img src="panther.png" alt="Panther"></h1>

**A browser testing and web scraping library for [PHP](https://php.net) and [Symfony](https://symfony.com)**

[![Build Status](https://travis-ci.org/symfony/panther.svg?branch=master)](https://travis-ci.org/symfony/panther)
[![Build status](https://ci.appveyor.com/api/projects/status/bunoc4ufud4oie45?svg=true)](https://ci.appveyor.com/project/fabpot/panther)
[![SensioLabsInsight](https://insight.sensiolabs.com/projects/9ea7e78c-998a-4489-9815-7449ce8291ef/mini.png)](https://insight.sensiolabs.com/projects/9ea7e78c-998a-4489-9815-7449ce8291ef)

*Panther* is a convenient standalone library to scrape websites and to run end-to-end tests **using real browsers**.

Panther is super powerful, it leverages [the W3C's WebDriver protocol](https://www.w3.org/TR/webdriver/) to drive native web browsers such as Google Chrome and Firefox.

Panther is very easy to use, because it implements the popular Symfony's [BrowserKit](https://symfony.com/doc/current/components/browser_kit.html) and
[DomCrawler](https://symfony.com/doc/current/components/dom_crawler.html) APIs, and contains
all features you need to test your apps. It will sound familiar if you have ever created [a functional test for a Symfony app](https://symfony.com/doc/current/testing.html#functional-tests):
as the API is exactly the same!
Keep in mind that Panther can be used in every PHP project, it's a standalone library.

Panther automatically finds your local installation of Chrome and launches it (thanks to [ChromeDriver](https://sites.google.com/a/chromium.org/chromedriver/)),
so you don't need to install anything on your computer, neither Selenium server nor obscure driver.

In test mode, Panther automatically starts your application using [the PHP built-in web-server](http://php.net/manual/en/features.commandline.webserver.php).
You can just focus on writing your tests or web-scraping scenario, Panther takes care of everything else.

## Install

Use [Composer](https://getcomposer.org/) to install Panther in your project. You may want to use the --dev flag if you want to use Panther for testing only and not for web scraping:

    composer req symfony/panther:dev-master
    
    composer req --dev symfony/panther:dev-master

## Basic Usage

```php
<?php

require __DIR__.'/vendor/autoload.php'; // Composer's autoloader

$client = \Symfony\Component\Panther\Client::createChromeClient();
$crawler = $client->request('GET', 'http://api-platform.com'); // Yes, this website is 100% in JavaScript

$link = $crawler->selectLink('Support')->link();
$crawler = $client->click($link);

// Wait for an element to be rendered
$client->waitFor('.support');

echo $crawler->filter('.support')->text();
$client->takeScreenshot('screen.png'); // Yeah, screenshot!
```

## Testing Usage

The `PantherTestCase` class allows you to easily write E2E tests. It automatically starts your app using the built-in PHP
web server and let you crawl it using Panther.
It extends [PHPUnit](https://phpunit.de/)'s `TestCase` and provide all testing tools you're used to.

```php
<?php

use Symfony\Component\Panther\PantherTestCase;

class E2eTest extends PantherTestCase
{
    public function testMyApp()
    {
        $client = static::createPantherClient(); // Your app is automatically started using the built-in web server
        $crawler = $client->request('GET', '/mypage');

        $this->assertContains('My Title', $crawler->filter('title')->text()); // You can use any PHPUnit assertion
    }
}
```

To run this test:

    phpunit tests/E2eTest.php

### A Polymorph Feline

If you are testing a Symfony application, `PantherTestCase` automatically extends the `WebTestCase` class. It means
you can easily create functional tests, which can directly execute the kernel of your application and access all your existing
services. Unlike the Panther's client, the Symfony's testing client doesn't support JavaScript and screenshots capturing, but
it is super-fast!

Alternatively (and even for non-Symfony apps), Panther can also leverage the [Goutte](https://github.com/FriendsOfPHP/Goutte)
web scraping library, which is an intermediate between the Symfony's and the Panther's test clients. Goutte sends real HTTP
requests, it is fast and is able to browse any webpage, not only the ones of the application under test.
But Goutte doesn't support JavaScript and other advanced features because it is entirely written in PHP.

The fun part is that the 3 libraries implement the exact same API, so you can switch from one to another just by calling
the appropriate factory method, and find the good trade off for every single test case (do I need JavaScript, do I need
to authenticate to an external SSO server, do I want to access the kernel of the current request...).

```php
<?php

use Symfony\Component\Panther\PantherTestCase;

class E2eTest extends PantherTestCase
{
    public function testMyApp()
    {
        $symfonyClient = static::createClient(); // A cute kitty: the Symfony's functional test too
        $goutteClient = static::createGoutteClient(); // An agile lynx: Goutte
        $pantherClient = static::createPantherClient(); // A majestic Panther
        
        // Both Goutte and Panther benefits from the built-in HTTP server
        
        // enjoy the same API for the 3 felines
        // $*client->request('GET', '...')

        $kernel = static::createKernel(); // You can also access to the app's kernel

        // ...
    }
}
```

## Features

Unlike testing and web scraping libraries you're used to, Panther:

* executes the JavaScript code contained in webpages
* supports everything that Chrome (or Firefox) implements
* allows screenshots taking
* can wait for the appearance of elements loaded asynchronously 
* lets you run your own JS code or XPath queries in the context of the loaded page
* supports custom [Selenium server](https://www.seleniumhq.org) installations
* supports remote browser testing services including [SauceLabs](https://saucelabs.com/) and [BrowserStack](https://www.browserstack.com/)

## Documentation

Since Panther implements the API of popular libraries, it already has an extensive documentation:

* For the `Client` class, read [the BrowserKit's documentation](https://symfony.com/doc/current/components/browser_kit.html)
* For the `Crawler` class, read [the DomCrawler's documentation](https://symfony.com/doc/current/components/dom_crawler.html)
* For Webdriver, read [the Facebook's PHP WebDriver documentation](https://github.com/facebook/php-webdriver)

## Environment Variables

The following environment variables can be set to change some Panther behaviors:

* `PANTHER_NO_HEADLESS`: to disable browsers's headless mode (will display the testing window, useful to debug)
* `PANTHER_NO_SANDBOX`: to disable [Chrome's sandboxing](https://chromium.googlesource.com/chromium/src/+/b4730a0c2773d8f6728946013eb812c6d3975bec/docs/design/sandbox.md) (unsafe, but allows to use Panther in containers)
* `PANTHER_WEB_SERVER_DIR`: to change the project's document root (default to `public/`)
* `PANTHER_CHROME_DRIVER_BINARY`: to use another `chromedriver` binary, instead of relying on the ones already provided by Panther

## Docker Integration

Here is a minimal Docker image that can run Panther:

```
FROM php:latest

RUN apt-get update && apt-get install -y zlib1g-dev chromium && docker-php-ext-install zip
ENV PANTHER_NO_SANDBOX 1
```

Build it with `docker build . -t myproject`
Run it with `docker run -it -v "$PWD":/srv/myproject -w /srv/myproject myproject bin/phpunit`

## Travis CI Integration

Panther will work out of the box with Travis if you add the Chrome addon. Here is a minimal `.travis.yml` file to run
Panther tests:

```yaml
language: php
addons:
  chrome: stable

php:
  - 7.2

script:
  - phpunit
```
## AppVeyor Integration

Panther will work out of the box with AppVeyor as long as Google Chrome is installed. Here is a minimal `appveyor.yml`
file to run Panther tests:

```yaml
build: false
platform: x86
clone_folder: c:\projects\myproject

cache:
  - '%LOCALAPPDATA%\Composer\files'

install:
  - ps: Set-Service wuauserv -StartupType Manual
  - cinst -y php composer googlechrome
  - refreshenv
  - cd c:\tools\php72
  - copy php.ini-production php.ini /Y
  - echo date.timezone="UTC" >> php.ini
  - echo extension_dir=ext >> php.ini
  - echo extension=php_openssl.dll >> php.ini
  - echo extension=php_mbstring.dll >> php.ini
  - echo extension=php_curl.dll >> php.ini
  - echo memory_limit=3G >> php.ini
  - cd %APPVEYOR_BUILD_FOLDER%
  - composer install --no-interaction --no-progress

test_script:
  - cd %APPVEYOR_BUILD_FOLDER%
  - php vendor\phpunit\phpunit\phpunit
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
