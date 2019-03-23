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

namespace Symfony\Component\Panther\DomCrawler\Field;

use Symfony\Component\DomCrawler\Field\FileFormField as BaseFileFormField;

/**
 * @author Robert Freigang <robertfreigang@gmx.de>
 */
final class FileFormField extends BaseFileFormField
{
    use FormFieldTrait;

    /**
     * Initializes the form field.
     *
     * @throws \LogicException When node type is incorrect
     */
    protected function initialize()
    {
        $tagName = $this->element->getTagName();
        if ('input' !== $tagName) {
            throw new \LogicException(\sprintf('An FileFormField can only be created from an input tag (%s given).', $tagName));
        }

        $type = \strtolower($this->element->getAttribute('type'));
        if ('file' !== $type) {
            throw new \LogicException(
                \sprintf(
                    'A FileFormField can only be created from an input tag with a type of file (given type is %s).',
                    $type
                )
            );
        }

        $this->setValue(null);
    }
}
