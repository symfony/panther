<h1 align="center"><img src="panther.png" alt="Panther"></h1>

**A browser testing and web scraping library for [PHP](https://php.net) and [Symfony](https://symfony.com)**

[![Build Status](https://travis-ci.org/symfony/panther.svg?branch=master)](https://travis-ci.org/symfony/panther)
[![Build status](https://ci.appveyor.com/api/projects/status/bunoc4ufud4oie45?svg=true)](https://ci.appveyor.com/project/fabpot/panther)
[![SensioLabsInsight](https://insight.sensiolabs.com/projects/9ea7e78c-998a-4489-9815-7449ce8291ef/mini.png)](https://insight.sensiolabs.com/projects/9ea7e78c-998a-4489-9815-7449ce8291ef)

*Panther* is a convenient standalone library to scrape websites and to run end-to-end tests **using real browsers**.

Panther is super powerful. It leverages [the W3C's WebDriver protocol](https://www.w3.org/TR/webdriver/) to drive native web browsers such as Google Chrome and Firefox.

Panther is very easy to use, because it implements Symfony's popular [BrowserKit](https://symfony.com/doc/current/components/browser_kit.html) and
[DomCrawler](https://symfony.com/doc/current/components/dom_crawler.html) APIs, and contains
all the features you need to test your apps. It will sound familiar if you have ever created [a functional test for a Symfony app](https://symfony.com/doc/current/testing.html#functional-tests):
as the API is exactly the same!
Keep in mind that Panther can be used in every PHP project, as it is a standalone library.

Panther automatically finds your local installation of Chrome and launches it (thanks to [ChromeDriver](https://sites.google.com/a/chromium.org/chromedriver/)),
so you don't need to install anything on your computer, neither Selenium server nor any other obscure driver.

In test mode, Panther automatically starts your application using [the PHP built-in web-server](http://php.net/manual/en/features.commandline.webserver.php).
You can focus on writing your tests or web-scraping scenario and Panther will take care of everything else.

## Features

Unlike testing and web scraping libraries you're used to, Panther:

* executes the JavaScript code contained in webpages
* supports everything that Chrome (or Firefox) implements
* allows screenshots taking
* can wait for asynchronously loaded elements to show up
* lets you run your own JS code or XPath queries in the context of the loaded page
* supports custom [Selenium server](https://www.seleniumhq.org) installations
* supports remote browser testing services including [SauceLabs](https://saucelabs.com/) and [BrowserStack](https://www.browserstack.com/)

## Documentation

### Install

Use [Composer](https://getcomposer.org/) to install Panther in your project. You may want to use the `--dev` flag if you want to use Panther for testing only and not for web scraping in a production environment:

    composer req symfony/panther

    composer req --dev symfony/panther

**Warning:** On \*nix systems, the `unzip` command must be installed or you will encounter an error similar to `RuntimeException: sh: 1: exec: /app/vendor/symfony/panther/src/ProcessManager/../../chromedriver-bin/chromedriver_linux64: Permission denied` (or `chromedriver_linux64: not found`).
The underlying reason is that PHP's `ZipArchive` doesn't preserve UNIX executable permissions.

#### Registering the PHPUnit Extension

If you intend to use Panther to test your application, we strongly recommended to register the Panther PHPUnit extension.
While not strictly mandatory, this extension dramatically improves the testing experience by boosting the performance and
allowing to use the [interactive debugging mode](#interactive-mode).

To register the Panther extension, add the following lines to `phpunit.xml.dist`:

```xml
<!-- phpunit.xml.dist -->
    <extensions>
        <extension class="Symfony\Component\Panther\ServerExtension" />
    </extensions>
```

Without the extension, the web server used by Panther to serve the application under test is started on demand and
stopped when called `tearDownAfterClass()` is called.
On the other hand, when the extension is registered, the web server will be stopped only after the very last test.

To use the Panther extension, PHPUnit 7.3+ is required. Nonetheless, a listener is provided for older versions:

```xml
<!-- phpunit.xml.dist -->
    <listeners>
        <listener class="Symfony\Component\Panther\ServerListener" />
    </listeners>
```

This listener will start the web server on demand like previously, but it will stop it after each test suite.

### Basic Usage

```php
<?php

require __DIR__.'/vendor/autoload.php'; // Composer's autoloader

$client = \Symfony\Component\Panther\Client::createChromeClient();
$crawler = $client->request('GET', 'https://api-platform.com'); // Yes, this website is 100% written in JavaScript

$crawler->clickLink('Support');

// Wait for an element to be rendered
$client->waitFor('.support');

echo $crawler->filter('.support')->text();
$client->takeScreenshot('screen.png'); // Yeah, screenshot!
```

### Testing Usage

The `PantherTestCase` class allows you to easily write E2E tests. It automatically starts your app using the built-in PHP
web server and let you crawl it using Panther.
To provides all of the testing tools you're used to, it extends [PHPUnit](https://phpunit.de/)'s `TestCase`.

If you are testing a Symfony application, `PantherTestCase` automatically extends [the `WebTestCase` class](https://symfony.com/doc/current/testing.html#functional-tests).
It means you can easily create functional tests, which can directly execute the kernel of your application and access all
your existing services. In this case, you can use [all crawler test assertions](https://symfony.com/doc/current/testing/functional_tests_assertions.html#crawler)
provided by Symfony with Panther.

```php
<?php

namespace App\Tests;

use Symfony\Component\Panther\PantherTestCase;

class E2eTest extends PantherTestCase
{
    public function testMyApp(): void
    {
        $client = static::createPantherClient(); // Your app is automatically started using the built-in web server
        $client->request('GET', '/mypage');

        // Use any PHPUnit assertion, including the ones provided by Symfony
        $this->assertPageTitleContains('My Title');
        $this->assertSelectorTextContains('#main', 'My body');
    }
}
```

To run this test:

    phpunit tests/E2eTest.php

### A Polymorphic Feline

Panther also gives you instant access to other BrowserKit-based implementations of `Client` and `Crawler`.
Unlike Panther's native client, these alternative clients don't support JavaScript, CSS and screenshot capturing,
but they are **super-fast**!

Two alternative clients are available:

* The first directly manipulates the Symfony kernel provided by `WebTestCase`. It is the fastest client available,
  but it is only available for Symfony apps.
* The second leverages the [Goutte](https://github.com/FriendsOfPHP/Goutte) web scraping library.
  It is an intermediate between Symfony's and Panther's test clients. Goutte sends real HTTP requests. 
  It is fast and is able to browse any webpage, not only the ones of the application under test.
  However, Goutte doesn't support JavaScript and other advanced features because it is entirely written in PHP.
  This one is available even for non-Symfony apps!

The fun part is that the 3 clients implement the exact same API, so you can switch from one to another just by calling
the appropriate factory method, resulting in a good trade-off for every single test case (Do I need JavaScript? Do I need
to authenticate with an external SSO server? Do I want to access the kernel of the current request? ... etc).

Here is how to retrieve instances of these clients:

```php
<?php

namespace App\Tests;

use Symfony\Component\Panther\PantherTestCase;
use Symfony\Component\Panther\Client;

class E2eTest extends PantherTestCase
{
    public function testMyApp()
    {
        $symfonyClient = static::createClient(); // A cute kitty: Symfony's functional test tool
        $goutteClient = static::createGoutteClient(); // An agile lynx: Goutte
        $pantherClient = static::createPantherClient(); // A majestic Panther
        // Both Goutte and Panther benefits from the built-in HTTP server

        $customChromeClient = Client::createChromeClient(null, null, [], 'https://example.com'); // Create a custom Chrome client
        $customSeleniumClient = Client::createSeleniumClient('http://127.0.0.1:4444/wd/hub', null, 'https://example.com'); // Create a custom Selenium client
        // When initializing a custom client, the integrated web server IS NOT started automatically.
        // Use PantherTestCase::startWebServer() or WebServerManager if you want to start it manually.

        // enjoy the same API for the 3 felines
        // $*client->request('GET', '...')

        $kernel = static::createKernel(); // If you are testing a Symfony app, you also have access to the kernel

        // ...
    }
}
```

### Creating Isolated Browsers to Test Apps Using [Mercure](https://mercure.rocks) or WebSocket

Panther provides a convenient way to test applications with real-time capabilities which use [Mercure](https://symfony.com/doc/current/mercure.html), [WebSocket](https://developer.mozilla.org/en-US/docs/Web/API/WebSockets_API)
and similar technologies.

`PantherTestCase::createAdditionalPantherClient()` creates additional, isolated browsers which can interact with each other.
For instance, this can be useful to test a chat application having several users connected simultaneously:

```php
<?php

use Symfony\Component\Panther\PantherTestCase;

class ChatTest extends PantherTestCase
{
    public function testChat(): void
    {
        $client1 = self::createPantherClient();
        $client1->request('GET', '/chat'); 
 
        // Connect a 2nd user using an isolated browser and say hi!
        $client2 = self::createAdditionalPantherClient();
        $client2->request('GET', '/chat');
        $client2->submitForm('Post message', ['message' => 'Hi folks 👋😻']);

        // Wait for the message to be received by the first client
        $client1->waitFor('.message');

        // Symfony Assertions are always executed in the **primary** browser
        $this->assertSelectorTextContains('.message', 'Hi folks 👋😻');
    }
}
```

## Additional Documentation

Since Panther implements the API of popular libraries, it already has extensive documentation:

* For the `Client` class, read [the BrowserKit documentation](https://symfony.com/doc/current/components/browser_kit.html)
* For the `Crawler` class, read [the DomCrawler documentation](https://symfony.com/doc/current/components/dom_crawler.html)
* For WebDriver, read [the Facebook PHP WebDriver documentation](https://github.com/facebook/php-webdriver)

### Environment Variables

The following environment variables can be set to change some Panther's behaviour:

* `PANTHER_NO_HEADLESS`: to disable browser's headless mode (will display the testing window, useful to debug)
* `PANTHER_NO_SANDBOX`: to disable [Chrome's sandboxing](https://chromium.googlesource.com/chromium/src/+/b4730a0c2773d8f6728946013eb812c6d3975bec/docs/design/sandbox.md) (unsafe, but allows to use Panther in containers)
* `PANTHER_WEB_SERVER_DIR`: to change the project's document root (default to `public/`)
* `PANTHER_CHROME_DRIVER_BINARY`: to use another `chromedriver` binary, instead of relying on the ones already provided by Panther
* `PANTHER_CHROME_ARGUMENTS`: to customize `chromedriver` arguments. You need to set `PANTHER_NO_HEADLESS` to fully customize.
* `PANTHER_WEB_SERVER_PORT`: to change the web server's port (default to `9080`)
* `PANTHER_WEB_SERVER_ROUTER`:  to use a web server router script which is run at the start of each HTTP request
* `PANTHER_EXTERNAL_BASE_URI`: to use an external web server (the PHP built-in web server will not be started)
* `PANTHER_CHROME_BINARY`: to use another `google-chrome` binary
* `PANTHER_APP_ENV`: to override the `APP_ENV` variable passed to the web server running the PHP app

### Accessing To Hidden Text

According to the spec, WebDriver implementations return only the **displayed** text by default.
When you filter on a `head` tag (like `title`), the method `text()` returns an empty string. Use the method `html()` to get
the complete contents of the tag, including the tag itself.

### Interactive Mode

Panther can make a pause in your tests suites after a failure.
It is a break time really appreciated for investigating the problem through the web browser.
For enabling this mode, you need the `--debug` PHPUnit option without the headless mode:

    $ export PANTHER_NO_HEADLESS=1
    $ phpunit --debug
    
    Test 'App\AdminTest::testLogin' started
    Error: something is wrong.
    
    Press enter to continue...

To use the interactive mode, the [PHPUnit extension](#registering-the-phpunit-extension) **must** be registered.

### Using an External Web Server

Sometimes, it's convenient to reuse an existing web server configuration instead of starting the built-in PHP one.
To do so, set the `external_base_uri` option:

```php
<?php

namespace App\Tests;

use Symfony\Component\Panther\PantherTestCase;

class E2eTest extends PantherTestCase
{
    public function testMyApp()
    {
        $pantherClient = static::createPantherClient(['external_base_uri' => 'https://localhost']);
        // the PHP integrated web server will not be started
    }
}
```

### Using a Proxy 

To use a proxy server, set the following environment variable: `PANTHER_CHROME_ARGUMENTS='--proxy-server=socks://127.0.0.1:9050'`

### Accepting Self-signed SSL Certificates

To force Chrome to accept invalid and self-signed certificates, set the following environment variable: `PANTHER_CHROME_ARGUMENTS='--ignore-certificate-errors'`
**This option is insecure**, use it only for testing in development environments, never in production (e.g. for web crawlers).

### Docker Integration

Here is a minimal Docker image that can run Panther:

```
FROM php:latest

RUN apt-get update && apt-get install -y libzip-dev zlib1g-dev chromium && docker-php-ext-install zip
ENV PANTHER_NO_SANDBOX 1
```

Build it with `docker build . -t myproject`
Run it with `docker run -it -v "$PWD":/srv/myproject -w /srv/myproject myproject bin/phpunit`

If you are using **Alpine Linux**, you may need to use another `chromedriver` binary.

```
RUN apk add --no-cache \
        chromium \
        chromium-chromedriver
ENV PANTHER_CHROME_DRIVER_BINARY /usr/lib/chromium/chromedriver
```

### Travis CI Integration

Panther will work out of the box with Travis if you add the Chrome addon. Here is a minimal `.travis.yml` file to run
Panther tests:

```yaml
language: php
addons:
  chrome: stable

php:
  - 7.1
  - 7.2

script:
  - phpunit
```

### AppVeyor Integration

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
  - cd c:\tools\php73
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

### Usage with Other Testing Tools

If you want to use Panther with other testing tools like [LiipFunctionalTestBundle](https://github.com/liip/LiipFunctionalTestBundle) or if you just need to use a different base class, Panther has got you covered. It provides you with the `Symfony\Component\Panther\PantherTestCaseTrait` and you can use it to enhance your existing test-infrastructure with some Panther awesomeness:

```php
<?php

namespace App\Tests\Controller;

use Liip\FunctionalTestBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Panther\PantherTestCaseTrait;

class DefaultControllerTest extends WebTestCase
{
    use PantherTestCaseTrait; // this is the magic. Panther is now available.

    public function testWithFixtures()
    {
        $this->loadFixtures([]); // load your fixtures
        $client = self::createPantherClient(); // create your panther client

        $client->request('GET', '/');
    }
}
```

## Limitations

The following features are not currently supported:

* Crawling XML documents (only HTML is supported)
* Updating existing documents (browsers are mostly used to consume data, not to create webpages)
* Setting form values using the multidimensional PHP array syntax
* Methods returning an instance of `\DOMElement` (because this library uses `WebDriverElement` internally)
* Selecting invalid choices in select

Pull Requests are welcome to fill the remaining gaps!

## Save the Panthers

Many of the wild cat species are highly threatened.
If you like this software, help save the (real) panthers by [donating to the Panthera organization](https://www.panthera.org/donate).

## Credits

Created by [Kévin Dunglas](https://dunglas.fr). Sponsored by [Les-Tilleuls.coop](https://les-tilleuls.coop).

Panther is built on top of [PHP WebDriver](https://github.com/facebook/php-webdriver) and [several other FOSS libraries](https://symfony.com/blog/introducing-symfony-panther-a-browser-testing-and-web-scrapping-library-for-php#thank-you-open-source). It has been inspired by [Nightwatch.js](http://nightwatchjs.org/), a WebDriver-based testing tool for JavaScript.
