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

use Facebook\WebDriver\Exception\ExpectedException;
use Symfony\Component\DomCrawler\Field\FileFormField;
use Symfony\Component\Panther\Tests\TestCase;

/**
 * @author Robert Freigang <robertfreigang@gmx.de>
 */
class FileFormFieldTest extends TestCase
{
    private static $uploadFileName = 'some-file.txt';
    private static $invalidUploadFileName = 'narf.txt';

    /**
     * @dataProvider clientFactoryProvider
     */
    public function testFileUpload(callable $clientFactory)
    {
        $crawler = $this->request($clientFactory, '/file-form-field.html');
        $form = $crawler->filter('form')->form();

        /** @var FileFormField */
        $fileFormField = $form['file_upload'];
        $this->assertInstanceOf(FileFormField::class, $fileFormField);
        $fileFormField->upload($this->getUploadFilePath());

        $this->assertContains(self::$uploadFileName, $form['file_upload']->getValue());
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

        if (isset($clientFactory[1]) && 'createGoutteClient' === $clientFactory[1]) {
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
        } elseif (isset($clientFactory[1]) && 'createPantherClient' === $clientFactory[1]) {
            $this->expectException(ExpectedException::class);
            $this->expectExceptionMessage(sprintf('File not found : %s', self::$invalidUploadFileName));
            $fileFormField->upload(self::$invalidUploadFileName);
        } else {
            $this->markAsRisky();
        }
    }

    private function getUploadFilePath(): string
    {
        $realpath = \realpath(sprintf('%s/%s', self::$webServerDir, self::$uploadFileName));
        if (\is_bool($realpath)) {
            $this->fail(sprintf('Could not create a realpath for file "%s"', self::$uploadFileName));

            return '';
        }

        return $realpath;
    }
}
