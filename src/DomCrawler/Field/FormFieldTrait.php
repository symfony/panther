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

namespace Symfony\Component\Panther\DomCrawler\Field;

use Facebook\WebDriver\WebDriverElement;
use Facebook\WebDriver\WebDriverKeys;
use Symfony\Component\Panther\ExceptionThrower;

/**
 * @internal
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
trait FormFieldTrait
{
    use ExceptionThrower;

    private WebDriverElement $element;

    public function __construct(WebDriverElement $element)
    {
        $this->element = $element;
        $this->initialize();
    }

    public function getLabel(): ?\DOMElement
    {
        throw $this->createNotSupportedException(__METHOD__);
    }

    public function getName(): string
    {
        return $this->element->getAttribute('name') ?? '';
    }

    public function getValue(): array|string|null
    {
        return $this->element->getAttribute('value');
    }

    public function isDisabled(): bool
    {
        return null !== $this->element->getAttribute('disabled');
    }

    private function setTextValue(?string $value): void
    {
        // Ensure to clean field before sending keys.
        // Unable to use $this->element->clear(); because it triggers a change event on it's own which is unexpected behavior.

        $v = $this->getValue();

        $existingValueLength = \strlen($v);
        $deleteKeys = str_repeat(WebDriverKeys::BACKSPACE.WebDriverKeys::DELETE, $existingValueLength);
        $this->element->sendKeys($deleteKeys.$value);
    }
}
