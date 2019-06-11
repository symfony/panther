CHANGELOG
=========

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
* Add a PHPUnit extension to keep alive the webserver and the client between tests 
* Change the default port of the web server to `9080` to prevent a conflict with Xdebug
* Allow to use an external web server instead of the built-in one for testing
* Allow to use a custom router script
* Allow to use a custom Chrome binary

0.2.0
-----

* Add JS execution capabilities to `Client`
* Allow keeping the webserver and client active even after test teardown
* Add a method to refresh the crawler (`Client::refreshCrawler()`)
* Add options to configure the web server and ChromeDriver
* PHP 7.1 compatibility
