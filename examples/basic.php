<?php

/*
 * This file is part of the Panthère project.
 *
 * (c) Kévin Dunglas <dunglas@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

require __DIR__.'/../vendor/autoload.php'; // Composer's autoloader

$client = \Panthere\Client::createChromeClient();
$crawler = $client->request('GET', 'http://api-platform.com'); // Yes, this website is 100% in JavaScript

$link = $crawler->selectLink('Support')->link();
$crawler = $client->click($link);

// Wait for an element to be rendered
$client->waitFor('.support');

// populate the search box with some search text - this will trigger a search box overlay to appear with results
$client->findElement(WebDriverBy::cssSelector('.search__input.ds-input'))
    ->sendKeys('how');

// Wait for the dynamic searchbox to appear
$client->waitFor('#algolia-autocomplete-listbox-0');

echo $crawler->filter('.support')->text();
$client->takeScreenshot('screen.png'); // Yeah, screenshot!
