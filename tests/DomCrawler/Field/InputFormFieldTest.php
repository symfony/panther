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
 * @author Robert Freigang <robertfreigang@gmx.de>
 */
class InputFormFieldTest extends TestCase
{
    #[DataProvider('clientFactoryProvider')]
    /**
     * @dataProvider clientFactoryProvider
     */
    public function testGetValueWithSomeValueFromTextInput(callable $clientFactory): void
    {
        $crawler = $this->request($clientFactory, '/input-form-field.html');
        $form = $crawler->filter('form')->form();

        /** @var InputFormField $field */
        $field = $form['text_input_with_some_value'];
        $this->assertInstanceOf(InputFormField::class, $field);
        $this->assertSame('some_value', $field->getValue());
    }

    #[DataProvider('clientFactoryProvider')]
    /**
     * @dataProvider clientFactoryProvider
     */
    public function testGetValueWithNoValueFromTextInput(callable $clientFactory): void
    {
        $crawler = $this->request($clientFactory, '/input-form-field.html');
        $form = $crawler->filter('form')->form();

        /** @var InputFormField $field */
        $field = $form['text_input_with_no_value'];
        $this->assertInstanceOf(InputFormField::class, $field);
        $this->assertSame('', $field->getValue());
    }

    #[DataProvider('clientFactoryProvider')]
    /**
     * @dataProvider clientFactoryProvider
     */
    public function testSetValueMultipleTimesInTextInput(callable $clientFactory): void
    {
        $crawler = $this->request($clientFactory, '/input-form-field.html');
        $form = $crawler->filter('form')->form();

        /** @var InputFormField $field */
        $field = $form['text_input_with_no_value'];
        $this->assertInstanceOf(InputFormField::class, $field);

        $field->setValue('first_value');
        $this->assertSame('first_value', $field->getValue());

        $field->setValue('second_value');
        $this->assertSame('second_value', $field->getValue());
    }
}
