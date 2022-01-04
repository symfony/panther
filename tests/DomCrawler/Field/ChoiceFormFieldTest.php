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

use Symfony\Component\DomCrawler\Field\ChoiceFormField;
use Symfony\Component\Panther\Client as PantherClient;
use Symfony\Component\Panther\Tests\TestCase;

/**
 * @author Robert Freigang <robertfreigang@gmx.de>
 */
class ChoiceFormFieldTest extends TestCase
{
    /**
     * @dataProvider clientFactoryProvider
     */
    public function testGetValueFromSelectIfOneIsSelected(callable $clientFactory): void
    {
        $crawler = $this->request($clientFactory, '/choice-form-field.html');
        $form = $crawler->filter('form')->form();

        /** @var ChoiceFormField $field */
        $field = $form['select_selected_one'];
        $this->assertInstanceOf(ChoiceFormField::class, $field);
        $this->assertSame('20', $field->getValue());
    }

    /**
     * @dataProvider clientFactoryProvider
     */
    public function testGetValueFromSelectIfNoneIsSelected(callable $clientFactory): void
    {
        $crawler = $this->request($clientFactory, '/choice-form-field.html');
        $form = $crawler->filter('form')->form();

        /** @var ChoiceFormField $field */
        $field = $form['select_selected_none'];
        $this->assertInstanceOf(ChoiceFormField::class, $field);
        $this->assertSame('', $field->getValue());
    }

    /**
     * @dataProvider clientFactoryProvider
     */
    public function testGetValueFromSelectMultipleIfOneIsSelected(callable $clientFactory): void
    {
        $crawler = $this->request($clientFactory, '/choice-form-field.html');
        $form = $crawler->filter('form')->form();

        /** @var ChoiceFormField $field */
        $field = $form['select_multiple_selected_one'];
        $this->assertInstanceOf(ChoiceFormField::class, $field);
        $this->assertSame(['20'], $field->getValue());
    }

    /**
     * @dataProvider clientFactoryProvider
     */
    public function testSetValueFromSelectMultipleIfOneIsSelectedAfterAllHaveBeenSelectedBefore(callable $clientFactory): void
    {
        $crawler = $this->request($clientFactory, '/choice-form-field.html');
        $form = $crawler->filter('form')->form();

        $field = $form['select_multiple_selected_all'];
        $this->assertInstanceOf(ChoiceFormField::class, $field);
        $this->assertSame(['10', '20', '30'], $field->getValue());
        $field->setValue('20');
        $this->assertSame(['20'], $field->getValue());
    }

    /**
     * @dataProvider clientFactoryProvider
     */
    public function testGetValueFromSelectMultipleIfMultipleIsSelected(callable $clientFactory): void
    {
        $crawler = $this->request($clientFactory, '/choice-form-field.html');
        $form = $crawler->filter('form')->form();

        /** @var ChoiceFormField $field */
        $field = $form['select_multiple_selected_multiple'];
        $this->assertInstanceOf(ChoiceFormField::class, $field);
        $this->assertSame(['20', '30'], $field->getValue());
    }

    /**
     * @dataProvider clientFactoryProvider
     */
    public function testGetValueFromSelectMultipleIfNoneIsSelected(callable $clientFactory): void
    {
        $crawler = $this->request($clientFactory, '/choice-form-field.html');
        $form = $crawler->filter('form')->form();

        /** @var ChoiceFormField $field */
        $field = $form['select_multiple_selected_none'];
        $this->assertInstanceOf(ChoiceFormField::class, $field);
        $this->assertSame([], $field->getValue());
    }

    /**
     * @dataProvider clientFactoryProvider
     */
    public function testGetValueFromRadioIfSelected(callable $clientFactory): void
    {
        $crawler = $this->request($clientFactory, '/choice-form-field.html');
        $form = $crawler->filter('form')->form();

        /** @var ChoiceFormField $field */
        $field = $form['radio_checked'];
        $this->assertInstanceOf(ChoiceFormField::class, $field);
        $this->assertSame('i_am_checked', $field->getValue());
    }

    /**
     * @dataProvider clientFactoryProvider
     */
    public function testGetValueFromRadioIfNoneIsChecked(callable $clientFactory): void
    {
        $crawler = $this->request($clientFactory, '/choice-form-field.html');
        $form = $crawler->filter('form')->form();

        /** @var ChoiceFormField $field */
        $field = $form['radio_non_checked'];
        $this->assertInstanceOf(ChoiceFormField::class, $field);
        $this->assertNull($field->getValue());
    }

    /**
     * @dataProvider clientFactoryProvider
     */
    public function testGetValueFromCheckboxIfChecked(callable $clientFactory): void
    {
        $crawler = $this->request($clientFactory, '/choice-form-field.html');
        $form = $crawler->filter('form')->form();

        /** @var ChoiceFormField $field */
        $field = $form['checkbox_checked'];
        $this->assertInstanceOf(ChoiceFormField::class, $field);
        $this->assertSame('i_am_checked', $field->getValue());
    }

    /**
     * @dataProvider clientFactoryProvider
     */
    public function testGetValueFromCheckboxIfMultipleAreChecked(callable $clientFactory, string $type): void
    {
        $crawler = $this->request($clientFactory, '/choice-form-field.html');
        $form = $crawler->filter('form')->form();

        /** @var ChoiceFormField $field */
        $field = $form['checkbox_multiple_checked'];
        $this->assertInstanceOf(ChoiceFormField::class, $field);
        // https://github.com/symfony/symfony/issues/26827
        if (PantherClient::class !== $type) {
            $this->markTestSkipped('The DomCrawler component doesn\'t support multiple fields with the same name');
        }
        $this->assertSame(['checked_one', 'checked_two'], $field->getValue());
    }

    /**
     * @dataProvider clientFactoryProvider
     */
    public function testSetValueFromCheckboxIfOneIsCheckedAfterAllHaveBeenCheckedBefore(callable $clientFactory, string $type): void
    {
        $crawler = $this->request($clientFactory, '/choice-form-field.html');
        $form = $crawler->filter('form')->form();

        $field = $form['checkbox_multiple_checked'];

        $this->assertInstanceOf(ChoiceFormField::class, $field);
        // https://github.com/symfony/symfony/issues/26827
        if (PantherClient::class !== $type) {
            $this->markTestSkipped('The DomCrawler component doesn\'t support multiple fields with the same name');
        }
        $this->assertSame(['checked_one', 'checked_two'], $field->getValue());
        $field->setValue('checked_two');
        $this->assertSame('checked_two', $field->getValue());
    }

    /**
     * @dataProvider clientFactoryProvider
     */
    public function testGetValueFromCheckboxIfNoneIsChecked(callable $clientFactory): void
    {
        $crawler = $this->request($clientFactory, '/choice-form-field.html');
        $form = $crawler->filter('form')->form();

        /** @var ChoiceFormField $field */
        $field = $form['checkbox_non_checked'];
        $this->assertInstanceOf(ChoiceFormField::class, $field);
        $this->assertNull($field->getValue());
    }
}
