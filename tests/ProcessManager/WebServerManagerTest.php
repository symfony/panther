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

namespace Symfony\Component\Panther\Tests\ProcessManager;

use Symfony\Component\Panther\ProcessManager\PhpWebServerFactory;
use Symfony\Component\Panther\Tests\TestCase;
use Symfony\Component\Panther\WebDriver\WebDriverCheckbox;

/**
 * @author Dominik Pfaffenbauer <dominik@pfaffenbauer.at>
 */
class WebServerManagerTest extends TestCase
{
    public function testAddingCustomWebServerFactory()
    {
        self::addWebServerFactory('custom_php', new PhpWebServerFactory());

        $client = self::createPantherClient(['webServer' => 'custom_php']);
        $crawler = $client->request('GET', self::$baseUri.'/form.html');

        $checkboxElement = $crawler->filterXPath('//input[@type="checkbox"]')->getElement(0);
        $c = new WebDriverCheckbox($checkboxElement);
        $this->assertTrue($c->isMultiple());
    }
}
