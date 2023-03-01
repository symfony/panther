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

use Symfony\Component\Panther\Client;

require __DIR__.'/../vendor/autoload.php'; // Composer's autoloader

$client = Client::createChromeClient();
// Or, if you care about the open web and prefer to use Firefox
// $client = Client::createFirefoxClient();

$client->request('GET', 'https://api-platform.com'); // Yes, this website is 100% written in JavaScript
$client->clickLink('Get started');

// Wait for an element to be present in the DOM (even if hidden)
$crawler = $client->waitFor('#installing-the-framework');
// Alternatively, wait for an element to be visible
$crawler = $client->waitForVisibility('#installing-the-framework');

echo $crawler->filter('#installing-the-framework')->text();
$client->takeScreenshot('screen.png'); // Yeah, screenshot!
