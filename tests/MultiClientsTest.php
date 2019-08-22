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

namespace Symfony\Component\Panther\Tests;

class MultiClientsTest extends TestCase
{
    public function testMultiClient(): void
    {
        $client = self::createPantherClient();
        $client->request('GET', '/cookie.php');

        $crawler = $client->request('GET', '/cookie.php');
        $this->assertSame('1', $crawler->filter('#barcelona')->text());

        $client2 = self::createAdditionalPantherClient();
        $crawler2 = $client2->request('GET', '/cookie.php');
        $this->assertSame('0', $crawler2->filter('#barcelona')->text());

        // Check that the cookie in the other client hasn't changed
        $this->assertSame('1', $crawler->filter('#barcelona')->text());
    }
}
