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

use Goutte\Client;
use Symfony\Component\DomCrawler\Field\ChoiceFormField;
use Symfony\Component\Panther\Tests\TestCase;

/**
 * @author Robert Freigang <robertfreigang@gmx.de>
 */
class ChoiceFormFieldTest extends TestCase
{
    /**
     * @dataProvider clientFactoryProvider
     */
    public function testGetValueFromSelectIfOneIsSelected(callable $clientFactory)
    {
        $crawler = $this->request($clientFactory, '/choice-form-field.html');
        $form = $crawler->filter('form')->form();

        /** @var ChoiceFormField */
        $field = $form['select_selected_one'];
        $this->assertInstanceOf(ChoiceFormField::class, $field);
        $this->assertSame('20', $field->getValue());
    }

    /**
     * @dataProvider clientFactoryProvider
     */
    public function testGetValueFromSelectIfNoneIsSelected(callable $clientFactory)
    {
        $crawler = $this->request($clientFactory, '/choice-form-field.html');
        $form = $crawler->filter('form')->form();

        /** @var ChoiceFormField */
        $field = $form['select_selected_none'];
        $this->assertInstanceOf(ChoiceFormField::class, $field);
        $this->assertSame('', $field->getValue());
    }

    /**
     * @dataProvider clientFactoryProvider
     */
    public function testGetValueFromSelectMultipleIfOneIsSelected(callable $clientFactory)
    {
        $crawler = $this->request($clientFactory, '/choice-form-field.html');
        $form = $crawler->filter('form')->form();

        /** @var ChoiceFormField */
        $field = $form['select_multiple_selected_one'];
        $this->assertInstanceOf(ChoiceFormField::class, $field);
        $this->assertSame(['20'], $field->getValue());
    }

    /**
     * @dataProvider clientFactoryProvider
     */
    public function testGetValueFromSelectMultipleIfMultipleIsSelected(callable $clientFactory)
    {
        $crawler = $this->request($clientFactory, '/choice-form-field.html');
        $form = $crawler->filter('form')->form();

        /** @var ChoiceFormField */
        $field = $form['select_multiple_selected_multiple'];
        $this->assertInstanceOf(ChoiceFormField::class, $field);
        $this->assertSame(['20', '30'], $field->getValue());
    }

    /**
     * @dataProvider clientFactoryProvider
     */
    public function testGetValueFromSelectMultipleIfNoneIsSelected(callable $clientFactory)
    {
        $crawler = $this->request($clientFactory, '/choice-form-field.html');
        $form = $crawler->filter('form')->form();

        /** @var ChoiceFormField */
        $field = $form['select_multiple_selected_none'];
        $this->assertInstanceOf(ChoiceFormField::class, $field);
        $this->assertSame([], $field->getValue());
    }

    /**
     * @dataProvider clientFactoryProvider
     */
    public function testGetValueFromRadioIfSelected(callable $clientFactory)
    {
        $crawler = $this->request($clientFactory, '/choice-form-field.html');
        $form = $crawler->filter('form')->form();

        /** @var ChoiceFormField */
        $field = $form['radio_checked'];
        $this->assertInstanceOf(ChoiceFormField::class, $field);
        $this->assertSame('i_am_checked', $field->getValue());
    }

    /**
     * @dataProvider clientFactoryProvider
     */
    public function testGetValueFromRadioIfNoneIsChecked(callable $clientFactory)
    {
        $crawler = $this->request($clientFactory, '/choice-form-field.html');
        $form = $crawler->filter('form')->form();

        /** @var ChoiceFormField */
        $field = $form['radio_non_checked'];
        $this->assertInstanceOf(ChoiceFormField::class, $field);
        $this->assertNull($field->getValue());
    }

    /**
     * @dataProvider clientFactoryProvider
     */
    public function testGetValueFromCheckboxIfChecked(callable $clientFactory)
    {
        $crawler = $this->request($clientFactory, '/choice-form-field.html');
        $form = $crawler->filter('form')->form();

        /** @var ChoiceFormField */
        $field = $form['checkbox_checked'];
        $this->assertInstanceOf(ChoiceFormField::class, $field);
        $this->assertSame('i_am_checked', $field->getValue());
    }

    /**
     * @dataProvider clientFactoryProvider
     */
    public function testGetValueFromCheckboxIfMultipleAreChecked(callable $clientFactory, string $type)
    {
        $crawler = $this->request($clientFactory, '/choice-form-field.html');
        $form = $crawler->filter('form')->form();

        /** @var ChoiceFormField */
        $field = $form['checkbox_multiple_checked'];
        $this->assertInstanceOf(ChoiceFormField::class, $field);
        // we need this one! but it's not working in goutte
        if (Client::class === $type) {
            $this->markTestSkipped('Goutte client only returns one value. Maybe a bug in goutte?');
        }
        $this->assertSame(['checked_one', 'checked_two'], $field->getValue());
    }

    /**
     * @dataProvider clientFactoryProvider
     */
    public function testGetValueFromCheckboxIfNoneIsChecked(callable $clientFactory)
    {
        $crawler = $this->request($clientFactory, '/choice-form-field.html');
        $form = $crawler->filter('form')->form();

        /** @var ChoiceFormField */
        $field = $form['checkbox_non_checked'];
        $this->assertInstanceOf(ChoiceFormField::class, $field);
        $this->assertNull($field->getValue());
    }
}
