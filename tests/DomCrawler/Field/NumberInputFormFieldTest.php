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

namespace Symfony\Component\Panther\Tests\DomCrawler\Field;

use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Component\DomCrawler\Field\InputFormField;
use Symfony\Component\Panther\Tests\TestCase;

/**
 * @author Dominik Pfaffenbauer <dominik@pfaffenbauer.at>
 */
class NumberInputFormFieldTest extends TestCase
{
    #[DataProvider('clientFactoryProvider')]
    /**
     * @dataProvider clientFactoryProvider
     */
    public function testGetValueWithSomeValueFromTextInput(callable $clientFactory): void
    {
        $crawler = $this->request($clientFactory, '/input-number-form-field.html');
        $form = $crawler->filter('form')->form();

        /** @var InputFormField $field */
        $field = $form['number_input_with_some_value'];
        $this->assertInstanceOf(InputFormField::class, $field);
        $this->assertSame('10', $field->getValue());
    }

    #[DataProvider('clientFactoryProvider')]
    /**
     * @dataProvider clientFactoryProvider
     */
    public function testGetValueWithNoValueFromTextInput(callable $clientFactory): void
    {
        $crawler = $this->request($clientFactory, '/input-number-form-field.html');
        $form = $crawler->filter('form')->form();

        /** @var InputFormField $field */
        $field = $form['number_input_with_no_value'];
        $this->assertInstanceOf(InputFormField::class, $field);
        $this->assertSame('', $field->getValue());
    }

    #[DataProvider('clientFactoryProvider')]
    /**
     * @dataProvider clientFactoryProvider
     */
    public function testSetValueMultipleTimesInTextInput(callable $clientFactory): void
    {
        $crawler = $this->request($clientFactory, '/input-number-form-field.html');
        $form = $crawler->filter('form')->form();

        /** @var InputFormField $field */
        $field = $form['number_input_with_no_value'];
        $this->assertInstanceOf(InputFormField::class, $field);

        $field->setValue('10');
        $this->assertSame('10', $field->getValue());

        $field->setValue('30');
        $this->assertSame('30', $field->getValue());
    }

    #[DataProvider('clientFactoryProvider')]
    /**
     * @dataProvider clientFactoryProvider
     */
    public function testChangeValueFromExistingValue(callable $clientFactory): void
    {
        $crawler = $this->request($clientFactory, '/input-number-form-field.html');
        $form = $crawler->filter('form')->form();

        /** @var InputFormField $field */
        $field = $form['number_input_with_some_value'];
        $this->assertInstanceOf(InputFormField::class, $field);

        $field->setValue('15');
        $this->assertSame('15', $field->getValue());
    }
}
