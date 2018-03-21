<?php

/*
 * This file is part of the Panthère project.
 *
 * (c) Kévin Dunglas <dunglas@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Panthere;

use Facebook\WebDriver\WebDriver;
use Facebook\WebDriver\WebDriverBy;
use Facebook\WebDriver\WebDriverExpectedCondition;
use Panthere\Cookie\CookieJar;
use Panthere\DomCrawler\Crawler;
use Panthere\DomCrawler\Form as PanthereForm;
use Panthere\DomCrawler\Link as PanthereLink;
use Panthere\ProcessManager\BrowserManagerInterface;
use Panthere\ProcessManager\ChromeManager;
use Symfony\Component\BrowserKit\Client as BaseClient;
use Symfony\Component\BrowserKit\Request;
use Symfony\Component\BrowserKit\Response;
use Symfony\Component\DomCrawler\Form;
use Symfony\Component\DomCrawler\Link;

/**
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
final class Client extends BaseClient implements WebDriver
{
    use ExceptionThrower;

    /**
     * @var WebDriver
     */
    private $webDriver;
    private $browserManager;

    /**
     * @param string[]|null $arguments
     */
    public static function createChromeClient(?string $chromeDriverBinary = null, ?array $arguments = null): self
    {
        return new self(new ChromeManager($chromeDriverBinary, $arguments));
    }

    public function __construct(BrowserManagerInterface $browserManager)
    {
        $this->browserManager = $browserManager;
    }

    public function __destruct()
    {
        $this->quit();
    }

    public function start()
    {
        if (null === $this->webDriver) {
            $this->webDriver = $this->browserManager->start();
        }
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
        if ($maxRedirects !== -1) {
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

    public function click(Link $link)
    {
        if ($link instanceof PanthereLink) {
            $link->getElement()->click();

            return $this->crawler = $this->createCrawler();
        }

        return parent::click($link);
    }

    public function submit(Form $form, array $values = [])
    {
        if ($form instanceof PanthereForm) {
            $button = $form->getButton();
            null === $button ? $form->getElement()->submit() : $button->click();

            return $this->crawler = $this->createCrawler();
        }

        return parent::submit($form, $values);
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
        throw new \Exception('Not useful in WebDriver mode.');
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

        $this->quit();
        $this->start();
    }

    public function getCookieJar()
    {
        $this->start();

        return new CookieJar($this->webDriver);
    }

    public function waitFor(string $cssSelector, int $timeoutInSecond = 30, int $intervalInMillisecond = 250): object
    {
        return $this->wait($timeoutInSecond, $intervalInMillisecond)->until(
            WebDriverExpectedCondition::visibilityOfElementLocated(WebDriverBy::cssSelector($cssSelector))
        );
    }

    public function getWebDriver(): WebDriver
    {
        $this->start();

        return $this->webDriver;
    }

    public function get($uri)
    {
        $this->start();

        $this->request = $this->internalRequest = new Request($uri, 'GET');
        $this->webDriver->get($uri);
        $this->response = $this->internalResponse = new Response($this->webDriver->getPageSource());
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

    public function quit()
    {
        if (null !== $this->webDriver) {
            $this->webDriver->quit();
            $this->webDriver = null;
        }
        $this->browserManager->quit();
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
}
