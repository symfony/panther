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

namespace Symfony\Component\Panther\Tests\DomCrawler\Field;

use Symfony\Component\DomCrawler\Field\InputFormField;
use Symfony\Component\Panther\Tests\TestCase;

/**
 * @author Dominik Pfaffenbauer <dominik@pfaffenbauer.at>
 */
class EmailInputFormFieldTest extends TestCase
{
    /**
     * @dataProvider clientFactoryProvider
     */
    public function testGetValueWithSomeValueFromTextInput(callable $clientFactory)
    {
        $crawler = $this->request($clientFactory, '/input-email-form-field.html');
        $form = $crawler->filter('form')->form();

        /** @var InputFormField $field */
        $field = $form['text_input_with_some_value'];
        $this->assertInstanceOf(InputFormField::class, $field);
        $this->assertSame('some_value', $field->getValue());
    }

    /**
     * @dataProvider clientFactoryProvider
     */
    public function testGetValueWithNoValueFromTextInput(callable $clientFactory)
    {
        $crawler = $this->request($clientFactory, '/input-email-form-field.html');
        $form = $crawler->filter('form')->form();

        /** @var InputFormField $field */
        $field = $form['text_input_with_no_value'];
        $this->assertInstanceOf(InputFormField::class, $field);
        $this->assertSame('', $field->getValue());
    }

    /**
     * @dataProvider clientFactoryProvider
     */
    public function testSetValueMultipleTimesInTextInput(callable $clientFactory)
    {
        $crawler = $this->request($clientFactory, '/input-email-form-field.html');
        $form = $crawler->filter('form')->form();

        /** @var InputFormField $field */
        $field = $form['text_input_with_no_value'];
        $this->assertInstanceOf(InputFormField::class, $field);

        $field->setValue('first@example.com');
        $this->assertSame('first@example.com', $field->getValue());

        $field->setValue('second@example.com');
        $this->assertSame('second@example.com', $field->getValue());
    }

    /**
     * @dataProvider clientFactoryProvider
     */
    public function testChangeValueFromExistingValue(callable $clientFactory)
    {
        $crawler = $this->request($clientFactory, '/input-email-form-field.html');
        $form = $crawler->filter('form')->form();

        /** @var InputFormField $field */
        $field = $form['text_input_with_some_value'];
        $this->assertInstanceOf(InputFormField::class, $field);

        $field->setValue('first@example.com');
        $this->assertSame('first@example.com', $field->getValue());
    }
}
