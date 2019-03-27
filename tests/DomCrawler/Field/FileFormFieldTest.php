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

use Symfony\Component\DomCrawler\Field\FileFormField;
use Symfony\Component\Panther\Tests\TestCase;

/**
 * @author Robert Freigang <robertfreigang@gmx.de>
 */
class FileFormFieldTest extends TestCase
{
    private static $invalidUploadFileName = 'narf.txt';

    /**
     * @dataProvider clientFactoryProvider
     */
    public function testFileUploadWithUpload(callable $clientFactory)
    {
        $crawler = $this->request($clientFactory, '/file-form-field.html');
        $form = $crawler->filter('form')->form();

        /** @var FileFormField */
        $fileFormField = $form['file_upload'];
        $this->assertInstanceOf(FileFormField::class, $fileFormField);
        $fileFormField->upload($this->getUploadFilePath(self::$uploadFileName));

        $this->assertContains(self::$uploadFileName, $form['file_upload']->getValue());
    }

    /**
     * @dataProvider clientFactoryProvider
     */
    public function testFileUploadWithSetValue(callable $clientFactory)
    {
        $crawler = $this->request($clientFactory, '/file-form-field.html');
        $form = $crawler->filter('form')->form();

        /** @var FileFormField */
        $fileFormField = $form['file_upload'];
        $this->assertInstanceOf(FileFormField::class, $fileFormField);
        $fileFormField->setValue($this->getUploadFilePath(self::$uploadFileName));

        $this->assertContains(self::$uploadFileName, $form['file_upload']->getValue());
    }

    /**
     * @dataProvider clientFactoryProvider
     */
    public function testFileUploadWithSetFilePath(callable $clientFactory)
    {
        $crawler = $this->request($clientFactory, '/file-form-field.html');
        $form = $crawler->filter('form')->form();

        /** @var FileFormField */
        $fileFormField = $form['file_upload'];
        $this->assertInstanceOf(FileFormField::class, $fileFormField);

        $fileFormField->setFilePath($this->getUploadFilePath(self::$uploadFileName));
        $this->assertContains(self::$uploadFileName, $form['file_upload']->getValue());

        $fileFormField->setFilePath($this->getUploadFilePath(self::$anotherUploadFileName));
        $this->assertContains(self::$anotherUploadFileName, $form['file_upload']->getValue());
    }

    /**
     * @dataProvider clientFactoryProvider
     */
    public function testFileUploadWithInvalidValue(callable $clientFactory)
    {
        $crawler = $this->request($clientFactory, '/file-form-field.html');
        $form = $crawler->filter('form')->form();

        /** @var FileFormField */
        $fileFormField = $form['file_upload'];
        $this->assertInstanceOf(FileFormField::class, $fileFormField);

        $fileFormField->upload(self::$invalidUploadFileName);
        $this->assertSame(
            [
                'name' => '',
                'type' => '',
                'tmp_name' => '',
                'error' => \UPLOAD_ERR_NO_FILE,
                'size' => 0,
            ],
            $fileFormField->getValue()
        );
    }

    /**
     * @dataProvider clientFactoryProvider
     */
    public function testPreventIsNotCanonicalError(callable $clientFactory)
    {
        $crawler = $this->request($clientFactory, '/file-form-field.html');
        $form = $crawler->filter('form')->form();

        /** @var FileFormField */
        $fileFormField = $form['file_upload'];
        $this->assertInstanceOf(FileFormField::class, $fileFormField);

        $nonCanonicalPath = \sprintf('%s/../fixtures/%s', self::$webServerDir, self::$uploadFileName);

        $fileFormField->upload($nonCanonicalPath);
        $fileFormField->setValue($nonCanonicalPath);
        $fileFormField->setFilePath($nonCanonicalPath);
    }
}
