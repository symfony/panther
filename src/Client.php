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

namespace Symfony\Component\Panther;

use Facebook\WebDriver\WebDriverCapabilities;
use Symfony\Component\HttpKernel\Kernel;
use Symfony\Component\Panther\DomCrawler\Crawler;
use Symfony\Component\Panther\ProcessManager\ChromeManager;
use Symfony\Component\Panther\ProcessManager\SeleniumManager;

if (Kernel::MAJOR_VERSION < 4) {
    class Client extends BaseClient
    {
        public static function createChromeClient(?string $chromeDriverBinary = null, ?array $arguments = null, array $options = [], ?string $baseUri = null): self
        {
            return new self(new ChromeManager($chromeDriverBinary, $arguments, $options), $baseUri);
        }

        public static function createSeleniumClient(?string $host = null, ?WebDriverCapabilities $capabilities = null, ?string $baseUri = null): self
        {
            return new self(new SeleniumManager($host, $capabilities), $baseUri);
        }

        public function request($method, $uri, array $parameters = [], array $files = [], array $server = [], $content = null, $changeHistory = true)
        {
            return $this->makeRequest($method, $uri, $parameters, $files, $server, $content, $changeHistory);
        }
    }
} else {
    class Client extends BaseClient
    {
        public static function createChromeClient(?string $chromeDriverBinary = null, ?array $arguments = null, array $options = [], ?string $baseUri = null): self
        {
            return new self(new ChromeManager($chromeDriverBinary, $arguments, $options), $baseUri);
        }

        public static function createSeleniumClient(?string $host = null, ?WebDriverCapabilities $capabilities = null, ?string $baseUri = null): self
        {
            return new self(new SeleniumManager($host, $capabilities), $baseUri);
        }

        public function request(string $method, string $uri, array $parameters = [], array $files = [], array $server = [], string $content = null, bool $changeHistory = true): Crawler
        {
            return $this->makeRequest($method, $uri, $parameters, $files, $server, $content, $changeHistory);
        }
    }
}
