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

namespace Symfony\Component\Panther\DomCrawler;

use Facebook\WebDriver\Exception\NoSuchElementException;
use Facebook\WebDriver\WebDriver;
use Facebook\WebDriver\WebDriverBy;
use Facebook\WebDriver\WebDriverElement;
use Symfony\Component\CssSelector\CssSelectorConverter;
use Symfony\Component\DomCrawler\Crawler as BaseCrawler;
use Symfony\Component\Panther\ExceptionThrower;

/**
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
final class Crawler extends BaseCrawler implements WebDriverElement
{
    use ExceptionThrower;

    private $elements;
    private $webDriver;

    /**
     * @param WebDriverElement[] $elements
     */
    public function __construct(array $elements = [], WebDriver $webDriver, ?string $uri = null)
    {
        $this->uri = $uri;
        $this->webDriver = $webDriver;
        $this->elements = $elements ?? [];
    }

    public function clear(): void
    {
        throw $this->createNotSupportedException(__METHOD__);
    }

    public function add($node): void
    {
        throw $this->createNotSupportedException(__METHOD__);
    }

    public function addContent($content, $type = null): void
    {
        throw $this->createNotSupportedException(__METHOD__);
    }

    public function addHtmlContent($content, $charset = 'UTF-8'): void
    {
        throw $this->createNotSupportedException(__METHOD__);
    }

    public function addXmlContent($content, $charset = 'UTF-8', $options = LIBXML_NONET): void
    {
        throw $this->createNotSupportedException(__METHOD__);
    }

    public function addDocument(\DOMDocument $dom): void
    {
        throw $this->createNotSupportedException(__METHOD__);
    }

    public function addNodeList(\DOMNodeList $nodes): void
    {
        throw $this->createNotSupportedException(__METHOD__);
    }

    public function addNodes(array $nodes): void
    {
        throw $this->createNotSupportedException(__METHOD__);
    }

    public function addNode(\DOMNode $node): void
    {
        throw $this->createNotSupportedException(__METHOD__);
    }

    public function eq($position): self
    {
        if (isset($this->elements[$position])) {
            return $this->createSubCrawler([$this->elements[$position]]);
        }

        return $this->createSubCrawler(null);
    }

    public function each(\Closure $closure): array
    {
        $data = [];
        foreach ($this->elements as $i => $element) {
            $data[] = $closure($this->createSubCrawler([$element]), $i);
        }

        return $data;
    }

    public function slice($offset = 0, $length = null): self
    {
        return $this->createSubCrawler(\array_slice($this->elements, $offset, $length));
    }

    public function reduce(\Closure $closure): self
    {
        $elements = [];
        foreach ($this->elements as $i => $element) {
            if (false !== $closure($this->createSubCrawler([$element]), $i)) {
                $elements[] = $element;
            }
        }

        return $this->createSubCrawler($elements);
    }

    public function last()
    {
        return $this->eq(\count($this->elements) - 1);
    }

    public function siblings()
    {
        return $this->createSubCrawlerFromXpath('(preceding-sibling::* | following-sibling::*)');
    }

    public function nextAll()
    {
        return $this->createSubCrawlerFromXpath('following-sibling::*');
    }

    public function previousAll()
    {
        return $this->createSubCrawlerFromXpath('preceding-sibling::*');
    }

    public function parents()
    {
        return $this->createSubCrawlerFromXpath('ancestor::*', true);
    }

    /**
     * @see https://github.com/symfony/symfony/issues/26432
     */
    public function children(string $selector = null)
    {
        $xpath = 'child::*';
        if (null !== $selector) {
            $converter = $this->createCssSelectorConverter();
            $xpath = $converter->toXPath($selector, 'child::');
        }

        return $this->createSubCrawlerFromXpath($xpath);
    }

    public function attr($attribute): ?string
    {
        $element = $this->getElementOrThrow();
        if ('_text' === $attribute) {
            return $this->text();
        }

        return (string) $element->getAttribute($attribute);
    }

    public function nodeName(): string
    {
        return $this->getElementOrThrow()->getTagName();
    }

    public function text(string $default = null, bool $normalizeWhitespace = true): string
    {
        if (!$normalizeWhitespace) {
            throw new \InvalidArgumentException('Panther only supports getting normalized text.');
        }

        try {
            return $this->getElementOrThrow()->getText();
        } catch (\InvalidArgumentException $e) {
            if (null === $default) {
                throw $e;
            }

            return $default;
        }
    }

    public function html($default = null): string
    {
        try {
            $element = $this->getElementOrThrow();

            if ('html' === $element->getTagName()) {
                return $this->webDriver->getPageSource();
            }

            return $this->attr('outerHTML');
        } catch (\InvalidArgumentException $e) {
            if (null === $default) {
                throw $e;
            }

            return (string) $default;
        }
    }

    public function evaluate($xpath): self
    {
        throw $this->createNotSupportedException(__METHOD__);
    }

    public function extract($attributes)
    {
        $attributes = (array) $attributes;
        $count = \count($attributes);

        $data = [];
        foreach ($this->elements as $element) {
            $elements = [];
            foreach ($attributes as $attribute) {
                $elements[] = '_text' === $attribute ? $element->getText() : (string) $element->getAttribute($attribute);
            }

            $data[] = 1 === $count ? $elements[0] : $elements;
        }

        return $data;
    }

    public function filterXPath($xpath): self
    {
        return $this->filterWebDriverBy(WebDriverBy::xpath($xpath));
    }

    public function filter($selector): self
    {
        return $this->filterWebDriverBy(WebDriverBy::cssSelector($selector));
    }

    public function selectLink($value)
    {
        return $this->selectFromXpath(
            \sprintf('descendant-or-self::a[contains(concat(\' \', normalize-space(string(.)), \' \'), %1$s) or ./img[contains(concat(\' \', normalize-space(string(@alt)), \' \'), %1$s)]]', static::xpathLiteral(' '.$value.' '))
        );
    }

    public function selectImage($value)
    {
        return $this->selectFromXpath(\sprintf('descendant-or-self::img[contains(normalize-space(string(@alt)), %s)]', static::xpathLiteral($value)));
    }

    public function selectButton($value)
    {
        return $this->selectFromXpath(
            \sprintf(
                'descendant-or-self::input[((contains(%1$s, "submit") or contains(%1$s, "button")) and contains(concat(\' \', normalize-space(string(@value)), \' \'), %2$s)) or (contains(%1$s, "image") and contains(concat(\' \', normalize-space(string(@alt)), \' \'), %2$s)) or @id=%3$s or @name=%3$s] | descendant-or-self::button[contains(concat(\' \', normalize-space(string(.)), \' \'), %2$s) or @id=%3$s or @name=%3$s]',
                'translate(@type, "ABCDEFGHIJKLMNOPQRSTUVWXYZ", "abcdefghijklmnopqrstuvwxyz")',
                static::xpathLiteral(' '.$value.' '),
                static::xpathLiteral($value)
            )
        );
    }

    public function link($method = 'get')
    {
        $element = $this->getElementOrThrow();
        if ('get' !== $method) {
            throw new \InvalidArgumentException('Only the "get" method is supported in WebDriver mode.');
        }

        return new Link($element, $this->webDriver->getCurrentURL());
    }

    public function links()
    {
        $links = [];
        foreach ($this->elements as $element) {
            $links[] = new Link($element, $this->webDriver->getCurrentURL());
        }

        return $links;
    }

    public function image()
    {
        return new Image($this->getElementOrThrow());
    }

    public function images()
    {
        $images = [];
        foreach ($this->elements as $element) {
            $images[] = new Image($element);
        }

        return $images;
    }

    public function form(array $values = null, $method = null)
    {
        $form = new Form($this->getElementOrThrow(), $this->webDriver);
        if (null !== $values) {
            $form->setValues($values);
        }

        return $form;
    }

    public function setDefaultNamespacePrefix($prefix)
    {
        throw $this->createNotSupportedException(__METHOD__);
    }

    public function registerNamespace($prefix, $namespace)
    {
        throw $this->createNotSupportedException(__METHOD__);
    }

    public function getNode($position): ?\DOMElement
    {
        throw new \InvalidArgumentException('The "getNode" method cannot be used in WebDriver mode. Use "getElement" instead.');
    }

    public function getElement(int $position): ?WebDriverElement
    {
        return $this->elements[$position] ?? null;
    }

    public function count()
    {
        return \count($this->elements);
    }

    public function getIterator(): \ArrayIterator
    {
        return new \ArrayIterator($this->elements);
    }

    protected function sibling($node, $siblingDir = 'nextSibling')
    {
        throw $this->createNotSupportedException(__METHOD__);
    }

    private function selectFromXpath(string $xpath): self
    {
        $xpath = WebDriverBy::xpath($xpath);

        $data = [];
        foreach ($this->elements as $element) {
            $data = \array_merge($data, $element->findElements($xpath));
        }

        return $this->createSubCrawler($data);
    }

    /**
     * @param WebDriverElement[]|null $elements
     */
    private function createSubCrawler(?array $elements = null): self
    {
        return new static($elements ?? [], $this->webDriver, $this->uri);
    }

    private function createSubCrawlerFromXpath(string $selector, bool $reverse = false): self
    {
        try {
            $elements = $this->getElementOrThrow()->findElements(WebDriverBy::xpath($selector));
        } catch (NoSuchElementException $e) {
            return $this->createSubCrawler(null);
        }

        return $this->createSubCrawler($reverse ? \array_reverse($elements) : $elements);
    }

    private function filterWebDriverBy(WebDriverBy $selector): self
    {
        $subElements = [];
        foreach ($this->elements as $element) {
            $subElements = \array_merge(
                $subElements,
                $element->findElements($selector)
            );
        }

        return $this->createSubCrawler($subElements);
    }

    private function getElementOrThrow(): WebDriverElement
    {
        $element = $this->getElement(0);
        if (!$element) {
            throw new \InvalidArgumentException('The current node list is empty.');
        }

        return $element;
    }

    public function click()
    {
        return $this->getElementOrThrow()->click();
    }

    public function getAttribute($attributeName)
    {
        return $this->getElementOrThrow()->getAttribute($attributeName);
    }

    public function getCSSValue($cssPropertyName)
    {
        return $this->getElementOrThrow()->getCSSValue($cssPropertyName);
    }

    public function getLocation()
    {
        return $this->getElementOrThrow()->getLocation();
    }

    public function getLocationOnScreenOnceScrolledIntoView()
    {
        return $this->getElementOrThrow()->getLocationOnScreenOnceScrolledIntoView();
    }

    public function getSize()
    {
        return $this->getElementOrThrow()->getSize();
    }

    public function getTagName()
    {
        return $this->getElementOrThrow()->getTagName();
    }

    public function getText()
    {
        return $this->getElementOrThrow()->getText();
    }

    public function isDisplayed()
    {
        return $this->getElementOrThrow()->isDisplayed();
    }

    public function isEnabled()
    {
        return $this->getElementOrThrow()->isEnabled();
    }

    public function isSelected()
    {
        return $this->getElementOrThrow()->isSelected();
    }

    public function sendKeys($value)
    {
        return $this->getElementOrThrow()->sendKeys($value);
    }

    public function submit()
    {
        return $this->getElementOrThrow()->submit();
    }

    public function getID()
    {
        return $this->getElementOrThrow()->getID();
    }

    public function findElement(WebDriverBy $locator)
    {
        return $this->getElementOrThrow()->findElement($locator);
    }

    public function findElements(WebDriverBy $locator)
    {
        return $this->getElementOrThrow()->findElements($locator);
    }

    /**
     * @throws \LogicException If the CssSelector Component is not available
     */
    private function createCssSelectorConverter(): CssSelectorConverter
    {
        if (!class_exists(CssSelectorConverter::class)) {
            throw new \LogicException('To filter with a CSS selector, install the CssSelector component ("composer require symfony/css-selector"). Or use filterXpath instead.');
        }

        return new CssSelectorConverter();
    }
}
