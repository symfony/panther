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

namespace Symfony\Component\Panther\Tests\DomCrawler;

use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Component\DomCrawler\Field\ChoiceFormField;
use Symfony\Component\Panther\Tests\TestCase;

/**
 * @author Kévin Dunglas <kevin@dunglas.dev>
 */
class FormTest extends TestCase
{
    #[DataProvider('clientFactoryProvider')]
    /**
     * @dataProvider clientFactoryProvider
     */
    public function testFormByButton(callable $clientFactory): void
    {
        $crawler = $this->request($clientFactory, '/form.html');
        $buttons = $crawler->selectButton('OK');

        $this->assertCount(3, $buttons);

        $values = [
            'i1' => 'foo',
            'i2' => 'bar',
            'i3' => "baz\nbaz",
            'i4' => 'i4b',
            'i5' => ['i5b', 'i5c'],
        ];

        $form = $buttons->form($values);
        $this->assertSame('POST', $form->getMethod());
        $this->assertSame($values, $form->getValues());
        $this->assertTrue($form->has('i1'));
        $this->assertTrue($form->has('i5'));
        $this->assertFalse($form->has('notexist'));
    }

    #[DataProvider('clientFactoryProvider')]
    /**
     * @dataProvider clientFactoryProvider
     */
    public function testFormById(callable $clientFactory): void
    {
        $crawler = $this->request($clientFactory, '/form.html');
        $values = [
            'j1' => 'hello',
            'j2' => [], // Multiple checkboxes cannot be filled yet because of https://github.com/FriendsOfPHP/Goutte/issues/60
            'j3' => 'j3c',
        ];

        $form = $crawler->filter('#another-form')->form($values);
        // Remove the unchecked checkbox
        unset($values['j2']);

        $this->assertSame($values, $form->getValues());
        $this->assertSame(self::$baseUri.'/form-handle.php?j1=hello&j3=j3c', $form->getUri());
        $this->assertSame('GET', $form->getMethod());

        $this->assertSame('DELETE', $crawler->filter('#special-submit')->form()->getMethod());
    }

    #[DataProvider('clientFactoryProvider')]
    /**
     * @dataProvider clientFactoryProvider
     */
    public function testFormFields(callable $clientFactory): void
    {
        $crawler = $this->request($clientFactory, '/form.html');

        $form = $crawler->filter('#another-form')->form();

        /** @var ChoiceFormField $j3 */
        $j3 = $form['j3'];
        $j3->select('j3a');

        $originalValues = $form->getValues();
        unset($originalValues['single-cb']);

        /** @var ChoiceFormField $singleCb */
        $singleCb = $form['single-cb'];
        $singleCb->tick();
        $this->assertSame($originalValues + ['single-cb' => 'hello'], $form->getValues());
        $this->assertSame('hello', $form['single-cb']->getValue());

        $singleCb->untick();
        $this->assertSame($originalValues, $form->getValues());
    }

    #[DataProvider('clientFactoryProvider')]
    /**
     * @dataProvider clientFactoryProvider
     */
    public function testSelect(callable $clientFactory): void
    {
        $crawler = $this->request($clientFactory, '/form.html');
        $form = $crawler->filter('form')->form();
        $form['i1']->setValue('Durruti');
        $form['i2']->setValue('Бакунин');
        $form['i3']->setValue('Ferrer');

        /** @var ChoiceFormField $i4 */
        $i4 = $form['i4'];
        $i4->select('i4b');

        /** @var ChoiceFormField $i5 */
        $i5 = $form['i5'];
        $i5->select(['i5b', 'i5c']);

        $this->assertSame([
            'i1' => 'Durruti',
            'i2' => 'Бакунин',
            'i3' => 'Ferrer',
            'i4' => 'i4b',
            'i5' => ['i5b', 'i5c'],
        ], $form->getValues());
        $this->assertSame([
            'i1' => 'Durruti',
            'i2' => 'Бакунин',
            'i3' => 'Ferrer',
            'i4' => 'i4b',
            'i5' => ['i5b', 'i5c'],
        ], $form->getPhpValues());
    }

    #[DataProvider('clientFactoryProvider')]
    /**
     * @dataProvider clientFactoryProvider
     */
    public function testGetValuesDoesNotContainFiles(callable $clientFactory): void
    {
        $crawler = $this->request($clientFactory, '/form.html');
        $form = $crawler->filter('#file-form')->form();

        $form['file_upload']->setValue($this->getUploadFilePath(self::$uploadFileName));
        $form['k1']->setValue('narf');

        $this->assertContains('narf', $form->getValues());
        $this->assertNotContains(self::$uploadFileName, $form->getValues());
    }

    #[DataProvider('clientFactoryProvider')]
    /**
     * @dataProvider clientFactoryProvider
     */
    public function testGetFilesContainOnlyFiles(callable $clientFactory): void
    {
        $crawler = $this->request($clientFactory, '/form.html');
        $form = $crawler->filter('#file-form')->form();

        $form['file_upload']->setValue($this->getUploadFilePath(self::$uploadFileName));
        $form['k1']->setValue('narf');

        $files = $form->getFiles();
        $this->assertNotContains('narf', $files);
        $this->assertArrayHasKey('file_upload', $files);

        if (4 === $files['file_upload']['error']) {
            $this->markTestSkipped('File upload is currently buggy with Firefox'); // FIXME
        }

        $this->assertContains(self::$uploadFileName, $files['file_upload']);
    }
}
