CHANGELOG
=========

0.5.2
-----

* Fix a bug occurring when using a non-fresh client

0.5.1
-----

* Allow to override the `APP_ENV` environment variable passed to the web server by setting `PANTHER_APP_ENV`
* Fix using assertions with a client created through `PantherTestCase::createClient()`
* Don't call `PantherTestCase::getClient()` if this method isn't `static`
* Fix remaining deprecations

0.5.0
-----

* Add support for [Crawler test assertions](https://symfony.com/doc/current/testing/functional_tests_assertions.html#crawler)
* Add the `PantherTestCase::createAdditionalPantherClient()` to retrieve additional isolated browsers, useful to test applications using [Mercure](https://mercure.rocks) or [WebSocket](https://developer.mozilla.org/en-US/docs/Web/API/WebSockets_API)   
* Improved support for non-standard web server directories
* Allow the integrated web server to start even if the homepage doesn't return a 200 HTTP status code
* Increase default timeouts from 5 seconds to 30 seconds
* Improve error messages
* Add compatibility with Symfony 4.3
* Upgrade ChromeDriver to version 76.0.3809.68
* Various quality improvements

0.4.1
-----

* Remove the direct dependency to `symfony/contracts`

0.4.0
-----

* Speed up the boot sequence
* Add basic support for file uploads
* Add a `readinessPath` option to use a custom path for server readiness detection
* Fix the behavior of `ChoiceFormField::getValue()` to be consistent with other BrowserKit implementations
* Ensure to clean the previous content of field when using `TextareaFormField::setValue()` and `InputFormField::setValue()`

0.3.0
-----

* Add a new API to manipulate the mouse
* Keep the browser window open on fail, when running in non-headless mode
* Automatically open Chrome DevTools when running in non-headless mode
* PHPUnit 8 compatibility
* Add a PHPUnit extension to keep alive the web server and the client between tests 
* Change the default port of the web server to `9080` to prevent a conflict with Xdebug
* Allow to use an external web server instead of the built-in one for testing
* Allow to use a custom router script
* Allow to use a custom Chrome binary

0.2.0
-----

* Add JS execution capabilities to `Client`
* Allow keeping the web server and client active even after test teardown
* Add a method to refresh the crawler (`Client::refreshCrawler()`)
* Add options to configure the web server and ChromeDriver
* PHP 7.1 compatibility
