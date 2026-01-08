<?php

/*
 * This file is part of the Panther project.
 *
 * (c) Kévin Dunglas <kevin@dunglas.dev>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Symfony\Component\Panther\Tests\WebDriver;

use Facebook\WebDriver\Exception\NoSuchElementException;
use Facebook\WebDriver\Exception\UnsupportedOperationException;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Component\Panther\Tests\TestCase;
use Symfony\Component\Panther\WebDriver\WebDriverCheckbox;

/**
 * @author Kévin Dunglas <kevin@dunglas.dev>
 */
class WebDriverCheckBoxTest extends TestCase
{
    public function testWebDriverCheckboxIsMultiple(): void
    {
        $crawler = self::createPantherClient()->request('GET', self::$baseUri.'/form.html');

        $checkboxElement = $crawler->filterXPath('//input[@type="checkbox"]')->getElement(0);
        $c = new WebDriverCheckbox($checkboxElement);
        $this->assertTrue($c->isMultiple());

        $radioElement = $crawler->filterXPath('//input[@type="radio"]')->getElement(0);
        $c = new WebDriverCheckbox($radioElement);
        $this->assertFalse($c->isMultiple());
    }

    #[DataProvider('getOptionsDataProvider')]
    /**
     * @dataProvider getOptionsDataProvider
     */
    public function testWebDriverCheckboxGetOptions(string $type, array $options): void
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

    public static function getOptionsDataProvider(): iterable
    {
        yield ['checkbox', ['j2a', 'j2b', 'j2c']];
        yield ['radio', ['j3a', 'j3b', 'j3c']];
    }

    public function testWebDriverCheckboxGetFirstSelectedOption(): void
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

    #[DataProvider('selectByValueDataProvider')]
    /**
     * @dataProvider selectByValueDataProvider
     */
    public function testWebDriverCheckboxSelectByValue(string $type, array $selectedOptions): void
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

    public static function selectByValueDataProvider(): iterable
    {
        yield ['checkbox', ['j2b', 'j2c']];
        yield ['radio', ['j3b']];
    }

    public function testWebDriverCheckboxSelectByValueInvalid(): void
    {
        $this->expectException(NoSuchElementException::class);

        $crawler = self::createPantherClient()->request('GET', self::$baseUri.'/form.html');
        $element = $crawler->filterXPath('//input[@type="checkbox"]')->getElement(0);

        $c = new WebDriverCheckbox($element);
        $c->selectByValue('notexist');
    }

    #[DataProvider('selectByIndexDataProvider')]
    /**
     * @dataProvider selectByIndexDataProvider
     */
    public function testWebDriverCheckboxSelectByIndex(string $type, array $selectedOptions): void
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
        $this->assertSame(array_values($selectedOptions), $selectedValues);
    }

    public static function selectByIndexDataProvider(): iterable
    {
        yield ['checkbox', [1 => 'j2b', 2 => 'j2c']];
        yield ['radio', [1 => 'j3b']];
    }

    public function testWebDriverCheckboxSelectByIndexInvalid(): void
    {
        $this->expectException(NoSuchElementException::class);

        $crawler = self::createPantherClient()->request('GET', self::$baseUri.'/form.html');
        $element = $crawler->filterXPath('//input[@type="checkbox"]')->getElement(0);

        $c = new WebDriverCheckbox($element);
        $c->selectByIndex(\PHP_INT_MAX);
    }

    #[DataProvider('selectByVisibleTextDataProvider')]
    /**
     * @dataProvider selectByVisibleTextDataProvider
     */
    public function testWebDriverCheckboxSelectByVisibleText(string $type, string $text, string $value): void
    {
        $crawler = self::createPantherClient()->request('GET', self::$baseUri.'/form.html');
        $element = $crawler->filterXPath("//input[@type='$type']")->getElement(0);
        $c = new WebDriverCheckbox($element);

        $c->selectByVisibleText($text);
        $this->assertSame($value, $c->getFirstSelectedOption()->getAttribute('value'));
    }

    public static function selectByVisibleTextDataProvider(): iterable
    {
        yield ['checkbox', 'J2B', 'j2b'];
        yield ['checkbox', 'J2C', 'j2c'];
        yield ['radio', 'J3B', 'j3b'];
        yield ['radio', 'J3C', 'j3c'];
    }

    #[DataProvider('selectByVisiblePartialTextDataProvider')]
    /**
     * @dataProvider selectByVisiblePartialTextDataProvider
     */
    public function testWebDriverCheckboxSelectByVisiblePartialText(string $type, string $text, string $value): void
    {
        $crawler = self::createPantherClient()->request('GET', self::$baseUri.'/form.html');
        $element = $crawler->filterXPath("//input[@type='$type']")->getElement(0);
        $c = new WebDriverCheckbox($element);

        $c->selectByVisiblePartialText($text);
        $this->assertSame($value, $c->getFirstSelectedOption()->getAttribute('value'));
    }

    public static function selectByVisiblePartialTextDataProvider(): iterable
    {
        yield ['checkbox', '2B', 'j2b'];
        yield ['checkbox', '2C', 'j2c'];
        yield ['radio', '3B', 'j3b'];
        yield ['radio', '3C', 'j3c'];
    }

    public function testWebDriverCheckboxDeselectAll(): void
    {
        $crawler = self::createPantherClient()->request('GET', self::$baseUri.'/form.html');
        $element = $crawler->filterXPath('//input[@type="checkbox"]')->getElement(0);
        $c = new WebDriverCheckbox($element);

        $c->selectByIndex(0);
        $this->assertCount(1, $c->getAllSelectedOptions());
        $c->deselectAll();
        $this->assertEmpty($c->getAllSelectedOptions());
    }

    public function testWebDriverCheckboxDeselectByIndex(): void
    {
        $crawler = self::createPantherClient()->request('GET', self::$baseUri.'/form.html');
        $element = $crawler->filterXPath('//input[@type="checkbox"]')->getElement(0);
        $c = new WebDriverCheckbox($element);

        $c->selectByIndex(0);
        $this->assertCount(1, $c->getAllSelectedOptions());
        $c->deselectByIndex(0);
        $this->assertEmpty($c->getAllSelectedOptions());
    }

    public function testWebDriverCheckboxDeselectByValue(): void
    {
        $crawler = self::createPantherClient()->request('GET', self::$baseUri.'/form.html');
        $element = $crawler->filterXPath('//input[@type="checkbox"]')->getElement(0);
        $c = new WebDriverCheckbox($element);

        $c->selectByValue('j2a');
        $this->assertCount(1, $c->getAllSelectedOptions());
        $c->deselectByValue('j2a');
        $this->assertEmpty($c->getAllSelectedOptions());
    }

    public function testWebDriverCheckboxDeselectByVisibleText(): void
    {
        $crawler = self::createPantherClient()->request('GET', self::$baseUri.'/form.html');
        $element = $crawler->filterXPath('//input[@type="checkbox"]')->getElement(0);
        $c = new WebDriverCheckbox($element);

        $c->selectByVisibleText('J2B');
        $this->assertCount(1, $c->getAllSelectedOptions());
        $c->deselectByVisibleText('J2B');
        $this->assertEmpty($c->getAllSelectedOptions());
    }

    public function testWebDriverCheckboxDeselectByVisiblePartialText(): void
    {
        $crawler = self::createPantherClient()->request('GET', self::$baseUri.'/form.html');
        $element = $crawler->filterXPath('//input[@type="checkbox"]')->getElement(0);
        $c = new WebDriverCheckbox($element);

        $c->selectByVisiblePartialText('2C');
        $this->assertCount(1, $c->getAllSelectedOptions());
        $c->deselectByVisiblePartialText('2C');
        $this->assertEmpty($c->getAllSelectedOptions());
    }

    public function testWebDriverCheckboxDeselectAllRadio(): void
    {
        $this->expectException(UnsupportedOperationException::class);

        $crawler = self::createPantherClient()->request('GET', self::$baseUri.'/form.html');
        $element = $crawler->filterXPath('//input[@type="radio"]')->getElement(0);
        $c = new WebDriverCheckbox($element);
        $c->deselectAll();
    }

    public function testWebDriverCheckboxDeselectByIndexRadio(): void
    {
        $this->expectException(UnsupportedOperationException::class);

        $crawler = self::createPantherClient()->request('GET', self::$baseUri.'/form.html');
        $element = $crawler->filterXPath('//input[@type="radio"]')->getElement(0);
        $c = new WebDriverCheckbox($element);
        $c->deselectByIndex(0);
    }

    public function testWebDriverCheckboxDeselectByValueRadio(): void
    {
        $this->expectException(UnsupportedOperationException::class);

        $crawler = self::createPantherClient()->request('GET', self::$baseUri.'/form.html');
        $element = $crawler->filterXPath('//input[@type="radio"]')->getElement(0);
        $c = new WebDriverCheckbox($element);
        $c->deselectByValue('val');
    }

    public function testWebDriverCheckboxDeselectByVisibleTextRadio(): void
    {
        $this->expectException(UnsupportedOperationException::class);

        $crawler = self::createPantherClient()->request('GET', self::$baseUri.'/form.html');
        $element = $crawler->filterXPath('//input[@type="radio"]')->getElement(0);
        $c = new WebDriverCheckbox($element);
        $c->deselectByVisibleText('AB');
    }

    public function testWebDriverCheckboxDeselectByVisiblePartialTextRadio(): void
    {
        $this->expectException(UnsupportedOperationException::class);

        $crawler = self::createPantherClient()->request('GET', self::$baseUri.'/form.html');
        $element = $crawler->filterXPath('//input[@type="radio"]')->getElement(0);
        $c = new WebDriverCheckbox($element);
        $c->deselectByVisiblePartialText('AB');
    }

    #[DataProvider('selectByValueDataProviderWithZeroValue')]
    /**
     * @dataProvider selectByValueDataProviderWithZeroValue
     */
    public function testWebDriverCheckboxSelectByValueWithZeroValue(string $type, string $selectedAndExpectedOption): void
    {
        $crawler = self::createPantherClient()->request('GET', self::$baseUri.'/form.html');
        $element = $crawler->filterXPath("//form[@id='zero-form-$type']/input")->getElement(0);

        $c = new WebDriverCheckbox($element);
        $c->selectByValue($selectedAndExpectedOption);

        $selectedValues = [];
        foreach ($c->getAllSelectedOptions() as $option) {
            $selectedValues[] = $option->getAttribute('value');
        }
        $this->assertSame([$selectedAndExpectedOption], $selectedValues);
    }

    public static function selectByValueDataProviderWithZeroValue(): iterable
    {
        yield ['checkbox', '0'];
        yield ['radio', '0'];
    }
}
