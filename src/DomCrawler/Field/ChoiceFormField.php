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

use Facebook\WebDriver\WebDriverSelect;
use Facebook\WebDriver\WebDriverSelectInterface;
use Symfony\Component\DomCrawler\Field\ChoiceFormField as BaseChoiceFormField;
use Symfony\Component\Panther\WebDriver\WebDriverCheckbox;

/**
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
final class ChoiceFormField extends BaseChoiceFormField
{
    use FormFieldTrait;

    /**
     * @var string
     */
    private $type;

    /**
     * @var WebDriverSelectInterface
     */
    private $selector;

    public function hasValue()
    {
        return $this->element->isSelected();
    }

    public function select($value)
    {
        foreach ((array) $value as $v) {
            $this->selector->selectByValue($v);
        }
    }

    /**
     * Ticks a checkbox.
     *
     * @throws \LogicException When the type provided is not correct
     */
    public function tick()
    {
        if ('checkbox' !== $type = $this->element->getAttribute('type')) {
            throw new \LogicException(\sprintf('You cannot tick "%s" as it is not a checkbox (%s).', $this->element->getAttribute('name'), $type));
        }

        $this->setValue(true);
    }

    /**
     * Ticks a checkbox.
     *
     * @throws \LogicException When the type provided is not correct
     */
    public function untick()
    {
        if ('checkbox' !== $type = $this->element->getAttribute('type')) {
            throw new \LogicException(\sprintf('You cannot tick "%s" as it is not a checkbox (%s).', $this->element->getAttribute('name'), $type));
        }

        $this->setValue(false);
    }

    /**
     * Sets the value of the field.
     *
     * @param string|array|bool $value The value of the field
     *
     * @throws \InvalidArgumentException When value type provided is not correct
     */
    public function setValue($value)
    {
        if (\is_bool($value)) {
            if ('checkbox' !== $this->type) {
                throw new \InvalidArgumentException(\sprintf('Invalid argument of type "%s"', \gettype($value)));
            }

            if ($value) {
                if (!$this->element->isSelected()) {
                    $this->element->click();
                }
            } elseif ($this->element->isSelected()) {
                $this->element->click();
            }

            return;
        }

        foreach ((array) $value as $v) {
            $this->selector->selectByValue($v);
        }
    }

    public function addChoice(\DOMElement $node)
    {
        $this->throwNotSupported(__METHOD__);
    }

    /**
     * Returns the type of the choice field (radio, select, or checkbox).
     *
     * @return string The type
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * Returns true if the field accepts multiple values.
     *
     * @return bool true if the field accepts multiple values, false otherwise
     */
    public function isMultiple()
    {
        return $this->selector->isMultiple();
    }

    /**
     * Returns list of available field options.
     *
     * @return array
     */
    public function availableOptionValues()
    {
        $options = [];

        foreach ($this->selector->getOptions() as $option) {
            $options[] = $option->getAttribute('value');
        }

        return $options;
    }

    /**
     * Disables the internal validation of the field.
     *
     * @return self
     */
    public function disableValidation()
    {
        $this->throwNotSupported(__METHOD__);
    }

    /**
     * Initializes the form field.
     *
     * @throws \LogicException When node type is incorrect
     */
    protected function initialize()
    {
        $tagName = $this->element->getTagName();
        if ('input' !== $tagName && 'select' !== $tagName) {
            throw new \LogicException(\sprintf('A ChoiceFormField can only be created from an input or select tag (%s given).', $tagName));
        }

        $type = \strtolower($this->element->getAttribute('type'));
        if ('input' === $tagName && 'checkbox' !== $type && 'radio' !== $type) {
            throw new \LogicException(\sprintf('A ChoiceFormField can only be created from an input tag with a type of checkbox or radio (given type is %s).', $type));
        }

        $this->type = 'select' === $tagName ? 'select' : $type;
        $this->selector = 'select' === $this->type ? new WebDriverSelect($this->element) : new WebDriverCheckbox($this->element);
    }
}
