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

namespace Symfony\Component\Panthere\DomCrawler;

use Facebook\WebDriver\WebDriverElement;
use Symfony\Component\DomCrawler\Link as BaseLink;
use Symfony\Component\Panthere\ExceptionThrower;

/**
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
final class Link extends BaseLink
{
    use ExceptionThrower;

    private $element;

    public function __construct(WebDriverElement $element)
    {
        $tagName = $element->getTagName();
        if ('a' !== $tagName && 'area' !== $tagName && 'link' !== $tagName) {
            throw new \LogicException(\sprintf('Unable to navigate from a "%s" tag.', $tagName));
        }

        $this->element = $element;
        $this->method = 'GET';
    }

    public function getElement(): WebDriverElement
    {
        return $this->element;
    }

    public function getNode()
    {
        $this->throwNotSupported(__METHOD__);
    }

    protected function setNode(\DOMElement $node)
    {
        $this->throwNotSupported(__METHOD__);
    }

    protected function getRawUri()
    {
        return $this->element->getAttribute('href');
    }
}
