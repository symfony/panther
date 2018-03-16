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

namespace Panthere\DomCrawler;

use Facebook\WebDriver\WebDriverElement;
use Panthere\ExceptionThrower;
use Symfony\Component\DomCrawler\Image as BaseImage;

/**
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
final class Image extends BaseImage
{
    use ExceptionThrower;

    private $element;

    public function __construct(WebDriverElement $element)
    {
        if ('img' !== $tagName = $element->getTagName()) {
            throw new \LogicException(\sprintf('Unable to visualize a "%s" tag.', $tagName));
        }

        $this->element = $element;
        $this->method = 'GET';
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
        return $this->element->getAttribute('src');
    }
}
