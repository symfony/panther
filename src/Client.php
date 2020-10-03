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

use Facebook\WebDriver\Exception\NoSuchElementException;
use Facebook\WebDriver\Exception\TimeoutException;
use Facebook\WebDriver\JavaScriptExecutor;
use Facebook\WebDriver\Remote\RemoteWebDriver;
use Facebook\WebDriver\WebDriver;
use Facebook\WebDriver\WebDriverBy;
use Facebook\WebDriver\WebDriverCapabilities;
use Facebook\WebDriver\WebDriverExpectedCondition;
use Facebook\WebDriver\WebDriverHasInputDevices;
use Symfony\Component\BrowserKit\AbstractBrowser;
use Symfony\Component\BrowserKit\Request;
use Symfony\Component\BrowserKit\Response;
use Symfony\Component\DomCrawler\Form;
use Symfony\Component\DomCrawler\Link;
use Symfony\Component\Panther\Cookie\CookieJar;
use Symfony\Component\Panther\DomCrawler\Crawler;
use Symfony\Component\Panther\DomCrawler\Form as PantherForm;
use Symfony\Component\Panther\DomCrawler\Link as PantherLink;
use Symfony\Component\Panther\ProcessManager\BrowserManagerInterface;
use Symfony\Component\Panther\ProcessManager\ChromeManager;
use Symfony\Component\Panther\ProcessManager\FirefoxManager;
use Symfony\Component\Panther\ProcessManager\SeleniumManager;
use Symfony\Component\Panther\WebDriver\WebDriverMouse;

/**
 * @author Kévin Dunglas <dunglas@gmail.com>
 * @author Dany Maillard <danymaillard93b@gmail.com>
 *
 * @method Crawler getCrawler()
 */
final class Client extends AbstractBrowser implements WebDriver, JavaScriptExecutor, WebDriverHasInputDevices
{
    use ExceptionThrower;

    /**
     * @var WebDriver|null
     */
    private $webDriver;
    private $browserManager;
    private $baseUri;
    private $isFirefox = false;

    /**
     * @param string[]|null $arguments
     */
    public static function createChromeClient(?string $chromeDriverBinary = null, ?array $arguments = null, array $options = [], ?string $baseUri = null): self
    {
        return new self(new ChromeManager($chromeDriverBinary, $arguments, $options), $baseUri);
    }

    public static function createFirefoxClient(?string $geckodriverBinary = null, ?array $arguments = null, array $options = [], ?string $baseUri = null): self
    {
        return new self(new FirefoxManager($geckodriverBinary, $arguments, $options), $baseUri);
    }

    public static function createSeleniumClient(?string $host = null, ?WebDriverCapabilities $capabilities = null, ?string $baseUri = null, array $options = []): self
    {
        return new self(new SeleniumManager($host, $capabilities, $options), $baseUri);
    }

    public function __construct(BrowserManagerInterface $browserManager, ?string $baseUri = null)
    {
        $this->browserManager = $browserManager;
        $this->baseUri = $baseUri;
    }

    public function getBrowserManager(): BrowserManagerInterface
    {
        return $this->browserManager;
    }

    public function __destruct()
    {
        $this->quit();
    }

    public function start()
    {
        if (null !== $this->webDriver) {
            return;
        }

        $this->webDriver = $this->browserManager->start();
        if ($this->browserManager instanceof FirefoxManager) {
            $this->isFirefox = true;

            return;
        }

        if ($this->browserManager instanceof ChromeManager) {
            $this->isFirefox = false;

            return;
        }

        if (method_exists($this->webDriver, 'getCapabilities')) {
            $this->isFirefox = 'firefox' === $this->webDriver->getCapabilities()->getBrowserName();

            return;
        }

        $this->isFirefox = false;
    }

    public function getRequest()
    {
        throw new \LogicException('HttpFoundation Request object is not available when using WebDriver.');
    }

    public function getResponse()
    {
        throw new \LogicException('HttpFoundation Response object is not available when using WebDriver.');
    }

    public function followRedirects($followRedirect = true): void
    {
        if (!$followRedirect) {
            throw new \InvalidArgumentException('Redirects are always followed when using WebDriver.');
        }
    }

    public function isFollowingRedirects(): bool
    {
        return true;
    }

    public function setMaxRedirects($maxRedirects): void
    {
        if (-1 !== $maxRedirects) {
            throw new \InvalidArgumentException('There are no max redirects when using WebDriver.');
        }
    }

    public function getMaxRedirects(): int
    {
        return -1;
    }

    public function insulate($insulated = true): void
    {
        if (!$insulated) {
            throw new \InvalidArgumentException('Requests are always insulated when using WebDriver.');
        }
    }

    public function setServerParameters(array $server): void
    {
        throw new \InvalidArgumentException('Server parameters cannot be set when using WebDriver.');
    }

    public function setServerParameter($key, $value): void
    {
        throw new \InvalidArgumentException('Server parameters cannot be set when using WebDriver.');
    }

    public function getServerParameter($key, $default = '')
    {
        throw new \InvalidArgumentException('Server parameters cannot be retrieved when using WebDriver.');
    }

    public function getHistory()
    {
        throw new \LogicException('History is not available when using WebDriver.');
    }

    public function click(Link $link)
    {
        if ($link instanceof PantherLink) {
            $link->getElement()->click();

            return $this->crawler = $this->createCrawler();
        }

        return parent::click($link);
    }

    public function submit(Form $form, array $values = [], array $serverParameters = [])
    {
        if ($form instanceof PantherForm) {
            foreach ($values as $field => $value) {
                $form->get($field)->setValue($value);
            }

            $button = $form->getButton();

            if ($this->isFirefox) {
                // For Firefox, we have to wait for the page to reload
                // https://github.com/SeleniumHQ/selenium/issues/4570#issuecomment-327473270
                $selector = WebDriverBy::cssSelector('html');
                $previousId = $this->webDriver->findElement($selector)->getID();

                null === $button ? $form->getElement()->submit() : $button->click();

                try {
                    $this->webDriver->wait(5)->until(static function (WebDriver $driver) use ($previousId, $selector) {
                        try {
                            return $previousId !== $driver->findElement($selector)->getID();
                        } catch (NoSuchElementException $e) {
                            // The html element isn't already available
                            return false;
                        }
                    });
                } catch (TimeoutException $e) {
                    // Probably a form using AJAX, do nothing
                }
            } else {
                null === $button ? $form->getElement()->submit() : $button->click();
            }

            return $this->crawler = $this->createCrawler();
        }

        return parent::submit($form, $values, $serverParameters);
    }

    public function refreshCrawler(): Crawler
    {
        return $this->crawler = $this->createCrawler();
    }

    public function request(string $method, string $uri, array $parameters = [], array $files = [], array $server = [], string $content = null, bool $changeHistory = true): Crawler
    {
        if ('GET' !== $method) {
            throw new \InvalidArgumentException('Only the GET method is supported when using WebDriver.');
        }
        if (null !== $content) {
            throw new \InvalidArgumentException('Setting a content is not supported when using WebDriver.');
        }
        if (!$changeHistory) {
            throw new \InvalidArgumentException('The history always change when using WebDriver.');
        }

        foreach (['parameters', 'files', 'server'] as $arg) {
            if ([] !== $$arg) {
                throw new \InvalidArgumentException(\sprintf('The parameter "$%s" is not supported when using WebDriver.', $arg));
            }
        }

        $this->get($uri);

        return $this->crawler;
    }

    protected function createCrawler(): Crawler
    {
        $this->start();
        $elements = $this->webDriver->findElements(WebDriverBy::cssSelector('html'));

        return new Crawler($elements, $this->webDriver, $this->webDriver->getCurrentURL());
    }

    protected function doRequest($request)
    {
        throw new \LogicException('Not useful in WebDriver mode.');
    }

    public function back()
    {
        $this->start();
        $this->webDriver->navigate()->back();

        return $this->crawler = $this->createCrawler();
    }

    public function forward()
    {
        $this->start();
        $this->webDriver->navigate()->forward();

        return $this->crawler = $this->createCrawler();
    }

    public function reload()
    {
        $this->start();
        $this->webDriver->navigate()->refresh();

        return $this->crawler = $this->createCrawler();
    }

    public function followRedirect()
    {
        throw new \LogicException('Redirects are always followed when using WebDriver.');
    }

    public function restart()
    {
        if (null !== $this->webDriver) {
            $this->webDriver->manage()->deleteAllCookies();
        }

        $this->quit(false);
        $this->start();
    }

    public function getCookieJar()
    {
        $this->start();

        return new CookieJar($this->webDriver);
    }

    /**
     * @param string $locator The path to an element to be waited for. Can be a CSS selector or Xpath expression.
     *
     * @throws NoSuchElementException
     * @throws TimeoutException
     *
     * @return Crawler
     */
    public function waitFor(string $locator, int $timeoutInSecond = 30, int $intervalInMillisecond = 250)
    {
        $by = $this->createWebDriverByFromLocator($locator);

        $this->wait($timeoutInSecond, $intervalInMillisecond)->until(
            WebDriverExpectedCondition::presenceOfElementLocated($by)
        );

        return $this->crawler = $this->createCrawler();
    }

    /**
     * @param string $locator The path to an element to be waited for. Can be a CSS selector or Xpath expression.
     *
     * @throws NoSuchElementException
     * @throws TimeoutException
     *
     * @return Crawler
     */
    public function waitForVisibility(string $locator, int $timeoutInSecond = 30, int $intervalInMillisecond = 250)
    {
        $by = $this->createWebDriverByFromLocator($locator);

        $this->wait($timeoutInSecond, $intervalInMillisecond)->until(
            WebDriverExpectedCondition::visibilityOfElementLocated($by)
        );

        return $this->crawler = $this->createCrawler();
    }

    public function getWebDriver(): WebDriver
    {
        $this->start();

        return $this->webDriver;
    }

    public function get($uri)
    {
        $this->start();

        // Prepend the base URI to URIs without a host
        if (null !== $this->baseUri && (false !== $components = \parse_url($uri)) && !isset($components['host'])) {
            $uri = $this->baseUri.$uri;
        }

        $this->internalRequest = new Request($uri, 'GET');
        $this->webDriver->get($uri);
        $this->internalResponse = new Response($this->webDriver->getPageSource());

        $this->crawler = $this->createCrawler();

        return $this;
    }

    public function close()
    {
        $this->start();

        return $this->webDriver->close();
    }

    public function getCurrentURL()
    {
        $this->start();

        return $this->webDriver->getCurrentURL();
    }

    public function getPageSource()
    {
        $this->start();

        return $this->webDriver->getPageSource();
    }

    public function getTitle()
    {
        $this->start();

        return $this->webDriver->getTitle();
    }

    public function getWindowHandle()
    {
        $this->start();

        return $this->webDriver->getWindowHandle();
    }

    public function getWindowHandles()
    {
        $this->start();

        return $this->webDriver->getWindowHandles();
    }

    public function quit(bool $quitBrowserManager = true)
    {
        if (null !== $this->webDriver) {
            $this->webDriver->quit();
            $this->webDriver = null;
        }

        if ($quitBrowserManager) {
            $this->browserManager->quit();
        }
    }

    public function takeScreenshot($saveAs = null)
    {
        $this->start();

        return $this->webDriver->takeScreenshot($saveAs);
    }

    public function wait($timeoutInSecond = 30, $intervalInMillisecond = 250)
    {
        $this->start();

        return $this->webDriver->wait($timeoutInSecond, $intervalInMillisecond);
    }

    public function manage()
    {
        $this->start();

        return $this->webDriver->manage();
    }

    public function navigate()
    {
        $this->start();

        return $this->webDriver->navigate();
    }

    public function switchTo()
    {
        $this->start();

        return $this->webDriver->switchTo();
    }

    public function execute($name, $params)
    {
        $this->start();

        return $this->webDriver->execute($name, $params);
    }

    public function findElement(WebDriverBy $locator)
    {
        $this->start();

        return $this->webDriver->findElement($locator);
    }

    public function findElements(WebDriverBy $locator)
    {
        $this->start();

        return $this->webDriver->findElements($locator);
    }

    public function executeScript($script, array $arguments = [])
    {
        if (!$this->webDriver instanceof JavaScriptExecutor) {
            throw $this->createException(JavaScriptExecutor::class);
        }

        return $this->webDriver->executeScript($script, $arguments);
    }

    public function executeAsyncScript($script, array $arguments = [])
    {
        if (!$this->webDriver instanceof JavaScriptExecutor) {
            throw $this->createException(JavaScriptExecutor::class);
        }

        return $this->webDriver->executeAsyncScript($script, $arguments);
    }

    public function getKeyboard()
    {
        if (!$this->webDriver instanceof WebDriverHasInputDevices) {
            throw $this->createException(WebDriverHasInputDevices::class);
        }

        return $this->webDriver->getKeyboard();
    }

    public function getMouse(): WebDriverMouse
    {
        if (!$this->webDriver instanceof WebDriverHasInputDevices) {
            throw $this->createException(WebDriverHasInputDevices::class);
        }

        return new WebDriverMouse($this->webDriver->getMouse(), $this);
    }

    private function createWebDriverByFromLocator(string $locator): WebDriverBy
    {
        $locator = trim($locator);

        return '' === $locator || '/' !== $locator[0]
            ? WebDriverBy::cssSelector($locator)
            : WebDriverBy::xpath($locator);
    }

    /**
     * Checks the web driver connection (and logs "pong" into the DevTools console).
     *
     * @param int $timeout sets the connection/request timeout in ms
     *
     * @return bool true if connected, false otherwise
     */
    public function ping(int $timeout = 1000): bool
    {
        if (null === $this->webDriver) {
            return false;
        }

        if ($this->webDriver instanceof RemoteWebDriver) {
            $this
                ->webDriver
                ->getCommandExecutor()
                ->setConnectionTimeout($timeout)
                ->setRequestTimeout($timeout);
        }

        try {
            if ($this->webDriver instanceof JavaScriptExecutor) {
                $this->webDriver->executeScript('console.log("pong");');
            } else {
                $this->webDriver->findElement(WebDriverBy::tagName('html'));
            }
        } catch (\Exception $e) {
            return false;
        } finally {
            if ($this->webDriver instanceof RemoteWebDriver) {
                $this
                    ->webDriver
                    ->getCommandExecutor()
                    ->setConnectionTimeout(0)
                    ->setRequestTimeout(0);
            }
        }

        return true;
    }

    /**
     * @return \LogicException|\RuntimeException
     */
    private function createException(string $implementableClass): \Exception
    {
        if (null === $this->webDriver) {
            return new \LogicException(sprintf('WebDriver not started yet. Call method `start()` first before calling any `%s` method.', $implementableClass));
        }

        return new \RuntimeException(sprintf('"%s" does not implement "%s".', \get_class($this->webDriver), $implementableClass));
    }
}
