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

namespace Symfony\Component\Panther\Tests\WebDriver;

use Symfony\Component\Panther\Tests\TestCase;
use Symfony\Component\Panther\WebDriver\WebDriverCheckbox;

/**
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
class WebDriverCheckBoxTest extends TestCase
{
    public function testWebDriverCheckboxIsMultiple()
    {
        $crawler = self::createPantherClient()->request('GET', self::$baseUri.'/form.html');

        $checkboxElement = $crawler->filterXPath('//input[@type="checkbox"]')->getElement(0);
        $c = new WebDriverCheckbox($checkboxElement);
        $this->assertTrue($c->isMultiple());

        $radioElement = $crawler->filterXPath('//input[@type="radio"]')->getElement(0);
        $c = new WebDriverCheckbox($radioElement);
        $this->assertFalse($c->isMultiple());
    }

    /**
     * @dataProvider getOptionsDataProvider
     */
    public function testWebDriverCheckboxGetOptions(string $type, array $options)
    {
        $crawler = self::createPantherClient()->request('GET', self::$baseUri.'/form.html');
        $element = $crawler->filterXPath("//input[@type='$type']")->getElement(0);

        $c = new WebDriverCheckbox($element);
        $values = [];
        foreach ($c->getOptions() as $option) {
            $values[] = $option->getAttribute('value');
        }

        $this->assertSame($options, $values);
    }

    public function getOptionsDataProvider()
    {
        return [
            ['checkbox', ['j2a', 'j2b', 'j2c']],
            ['radio', ['j3a', 'j3b', 'j3c']],
        ];
    }

    public function testWebDriverCheckboxGetFirstSelectedOption()
    {
        $crawler = self::createPantherClient()->request('GET', self::$baseUri.'/form.html');
        $checkboxElement = $crawler->filterXPath('//input[@type="checkbox"]')->getElement(0);

        $c = new WebDriverCheckbox($checkboxElement);
        $c->selectByValue('j2a');
        $this->assertSame('j2a', $c->getFirstSelectedOption()->getAttribute('value'));

        $radioElement = $crawler->filterXPath('//input[@type="radio"]')->getElement(0);
        $c = new WebDriverCheckbox($radioElement);
        $c->selectByValue('j3a');
        $this->assertSame('j3a', $c->getFirstSelectedOption()->getAttribute('value'));
    }

    /**
     * @dataProvider selectByValueDataProvider
     */
    public function testWebDriverCheckboxSelectByValue(string $type, array $selectedOptions)
    {
        $crawler = self::createPantherClient()->request('GET', self::$baseUri.'/form.html');
        $element = $crawler->filterXPath("//input[@type='$type']")->getElement(0);

        $c = new WebDriverCheckbox($element);
        foreach ($selectedOptions as $index => $selectedOption) {
            $c->selectByValue($selectedOption);
        }

        $selectedValues = [];
        foreach ($c->getAllSelectedOptions() as $option) {
            $selectedValues[] = $option->getAttribute('value');
        }
        $this->assertSame($selectedOptions, $selectedValues);
    }

    public function selectByValueDataProvider()
    {
        return [
            ['checkbox', ['j2b', 'j2c']],
            ['radio', ['j3b']],
        ];
    }

    /**
     * @expectedException \Facebook\WebDriver\Exception\NoSuchElementException
     */
    public function testWebDriverCheckboxSelectByValueInvalid()
    {
        $crawler = self::createPantherClient()->request('GET', self::$baseUri.'/form.html');
        $element = $crawler->filterXPath('//input[@type="checkbox"]')->getElement(0);

        $c = new WebDriverCheckbox($element);
        $c->selectByValue('notexist');
    }

    /**
     * @dataProvider selectByIndexDataProvider
     */
    public function testWebDriverCheckboxSelectByIndex(string $type, array $selectedOptions)
    {
        $crawler = self::createPantherClient()->request('GET', self::$baseUri.'/form.html');
        $element = $crawler->filterXPath("//input[@type='$type']")->getElement(0);

        $c = new WebDriverCheckbox($element);
        foreach ($selectedOptions as $index => $selectedOption) {
            $c->selectByIndex($index);
        }

        $selectedValues = [];
        foreach ($c->getAllSelectedOptions() as $option) {
            $selectedValues[] = $option->getAttribute('value');
        }
        $this->assertSame(\array_values($selectedOptions), $selectedValues);
    }

    public function selectByIndexDataProvider()
    {
        return [
            ['checkbox', [1 => 'j2b', 2 => 'j2c']],
            ['radio', [1 => 'j3b']],
        ];
    }

    /**
     * @expectedException \Facebook\WebDriver\Exception\NoSuchElementException
     */
    public function testWebDriverCheckboxSelectByIndexInvalid()
    {
        $crawler = self::createPantherClient()->request('GET', self::$baseUri.'/form.html');
        $element = $crawler->filterXPath('//input[@type="checkbox"]')->getElement(0);

        $c = new WebDriverCheckbox($element);
        $c->selectByIndex(PHP_INT_MAX);
    }

    /**
     * @dataProvider selectByVisibleTextDataProvider
     */
    public function testWebDriverCheckboxSelectByVisibleText(string $type, string $text, string $value)
    {
        $crawler = self::createPantherClient()->request('GET', self::$baseUri.'/form.html');
        $element = $crawler->filterXPath("//input[@type='$type']")->getElement(0);
        $c = new WebDriverCheckbox($element);

        $c->selectByVisibleText($text);
        $this->assertSame($value, $c->getFirstSelectedOption()->getAttribute('value'));
    }

    public function selectByVisibleTextDataProvider()
    {
        return [
            ['checkbox', 'J2B', 'j2b'],
            ['checkbox', 'J2C', 'j2c'],
            ['radio', 'J3B', 'j3b'],
            ['radio', 'J3C', 'j3c'],
        ];
    }

    /**
     * @dataProvider selectByVisiblePartialTextDataProvider
     */
    public function testWebDriverCheckboxSelectByVisiblePartialText(string $type, string $text, string $value)
    {
        $crawler = self::createPantherClient()->request('GET', self::$baseUri.'/form.html');
        $element = $crawler->filterXPath("//input[@type='$type']")->getElement(0);
        $c = new WebDriverCheckbox($element);

        $c->selectByVisiblePartialText($text);
        $this->assertSame($value, $c->getFirstSelectedOption()->getAttribute('value'));
    }

    public function selectByVisiblePartialTextDataProvider()
    {
        return [
            ['checkbox', '2B', 'j2b'],
            ['checkbox', '2C', 'j2c'],
            ['radio', '3B', 'j3b'],
            ['radio', '3C', 'j3c'],
        ];
    }

    public function testWebDriverCheckboxDeselectAll()
    {
        $crawler = self::createPantherClient()->request('GET', self::$baseUri.'/form.html');
        $element = $crawler->filterXPath('//input[@type="checkbox"]')->getElement(0);
        $c = new WebDriverCheckbox($element);

        $c->selectByIndex(0);
        $this->assertCount(1, $c->getAllSelectedOptions());
        $c->deselectAll();
        $this->assertEmpty($c->getAllSelectedOptions());
    }

    public function testWebDriverCheckboxDeselectByIndex()
    {
        $crawler = self::createPantherClient()->request('GET', self::$baseUri.'/form.html');
        $element = $crawler->filterXPath('//input[@type="checkbox"]')->getElement(0);
        $c = new WebDriverCheckbox($element);

        $c->selectByIndex(0);
        $this->assertCount(1, $c->getAllSelectedOptions());
        $c->deselectByIndex(0);
        $this->assertEmpty($c->getAllSelectedOptions());
    }

    public function testWebDriverCheckboxDeselectByValue()
    {
        $crawler = self::createPantherClient()->request('GET', self::$baseUri.'/form.html');
        $element = $crawler->filterXPath('//input[@type="checkbox"]')->getElement(0);
        $c = new WebDriverCheckbox($element);

        $c->selectByValue('j2a');
        $this->assertCount(1, $c->getAllSelectedOptions());
        $c->deselectByValue('j2a');
        $this->assertEmpty($c->getAllSelectedOptions());
    }

    public function testWebDriverCheckboxDeselectByVisibleText()
    {
        $crawler = self::createPantherClient()->request('GET', self::$baseUri.'/form.html');
        $element = $crawler->filterXPath('//input[@type="checkbox"]')->getElement(0);
        $c = new WebDriverCheckbox($element);

        $c->selectByVisibleText('J2B');
        $this->assertCount(1, $c->getAllSelectedOptions());
        $c->deselectByVisibleText('J2B');
        $this->assertEmpty($c->getAllSelectedOptions());
    }

    public function testWebDriverCheckboxDeselectByVisiblePartialText()
    {
        $crawler = self::createPantherClient()->request('GET', self::$baseUri.'/form.html');
        $element = $crawler->filterXPath('//input[@type="checkbox"]')->getElement(0);
        $c = new WebDriverCheckbox($element);

        $c->selectByVisiblePartialText('2C');
        $this->assertCount(1, $c->getAllSelectedOptions());
        $c->deselectByVisiblePartialText('2C');
        $this->assertEmpty($c->getAllSelectedOptions());
    }

    /**
     * @expectedException \Facebook\WebDriver\Exception\UnsupportedOperationException
     */
    public function testWebDriverCheckboxDeselectAllRadio()
    {
        $crawler = self::createPantherClient()->request('GET', self::$baseUri.'/form.html');
        $element = $crawler->filterXPath('//input[@type="radio"]')->getElement(0);
        $c = new WebDriverCheckbox($element);
        $c->deselectAll();
    }

    /**
     * @expectedException \Facebook\WebDriver\Exception\UnsupportedOperationException
     */
    public function testWebDriverCheckboxDeselectByIndexRadio()
    {
        $crawler = self::createPantherClient()->request('GET', self::$baseUri.'/form.html');
        $element = $crawler->filterXPath('//input[@type="radio"]')->getElement(0);
        $c = new WebDriverCheckbox($element);
        $c->deselectByIndex(0);
    }

    /**
     * @expectedException \Facebook\WebDriver\Exception\UnsupportedOperationException
     */
    public function testWebDriverCheckboxDeselectByValueRadio()
    {
        $crawler = self::createPantherClient()->request('GET', self::$baseUri.'/form.html');
        $element = $crawler->filterXPath('//input[@type="radio"]')->getElement(0);
        $c = new WebDriverCheckbox($element);
        $c->deselectByValue('val');
    }

    /**
     * @expectedException \Facebook\WebDriver\Exception\UnsupportedOperationException
     */
    public function testWebDriverCheckboxDeselectByVisibleTextRadio()
    {
        $crawler = self::createPantherClient()->request('GET', self::$baseUri.'/form.html');
        $element = $crawler->filterXPath('//input[@type="radio"]')->getElement(0);
        $c = new WebDriverCheckbox($element);
        $c->deselectByVisibleText('AB');
    }

    /**
     * @expectedException \Facebook\WebDriver\Exception\UnsupportedOperationException
     */
    public function testWebDriverCheckboxDeselectByVisiblePartialTextRadio()
    {
        $crawler = self::createPantherClient()->request('GET', self::$baseUri.'/form.html');
        $element = $crawler->filterXPath('//input[@type="radio"]')->getElement(0);
        $c = new WebDriverCheckbox($element);
        $c->deselectByVisiblePartialText('AB');
    }
}
