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

use Symfony\Component\DomCrawler\Field\TextareaFormField;
use Symfony\Component\Panther\Tests\TestCase;

/**
 * @author Robert Freigang <robertfreigang@gmx.de>
 */
class TextareaFormFieldTest extends TestCase
{
    /**
     * @dataProvider clientFactoryProvider
     */
    public function testGetValueWithSomeValue(callable $clientFactory)
    {
        $crawler = $this->request($clientFactory, '/textarea-form-field.html');
        $form = $crawler->filter('form')->form();

        /** @var TextareaFormField */
        $field = $form['textarea_with_some_value'];
        $this->assertInstanceOf(TextareaFormField::class, $field);
        $this->assertSame('some_value', $field->getValue());
    }

    /**
     * @dataProvider clientFactoryProvider
     */
    public function testGetValueWithNoValue(callable $clientFactory)
    {
        $crawler = $this->request($clientFactory, '/textarea-form-field.html');
        $form = $crawler->filter('form')->form();

        /** @var TextareaFormField */
        $field = $form['textarea_with_no_value'];
        $this->assertInstanceOf(TextareaFormField::class, $field);
        $this->assertSame('', $field->getValue());
    }

    /**
     * @dataProvider clientFactoryProvider
     */
    public function testSetValueMultipleTimes(callable $clientFactory)
    {
        $crawler = $this->request($clientFactory, '/textarea-form-field.html');
        $form = $crawler->filter('form')->form();

        /** @var TextareaFormField */
        $field = $form['textarea_with_no_value'];
        $this->assertInstanceOf(TextareaFormField::class, $field);

        $field->setValue('first_value');
        $this->assertSame('first_value', $field->getValue());

        $field->setValue('second_value');
        $this->assertSame('second_value', $field->getValue());
    }
}
