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
require __DIR__.'/../vendor/autoload.php';

use Facebook\WebDriver\WebDriverBy;
use Facebook\WebDriver\WebDriverExpectedCondition;
use Panthere\Client;

$client = new Client();
$crawler = $client->request('GET', 'http://api-platform.com'); // Yes, this website is 100% in JavaScript

$link = $crawler->selectLink('Support')->link();
$crawler = $client->click($link);

// Wait for an element
$client->wait()->until(
    WebDriverExpectedCondition::visibilityOfElementLocated(WebDriverBy::className('support'))
);

echo $crawler->filter('.support')->text();
$client->takeScreenshot('screen.png');

$client->quit();
