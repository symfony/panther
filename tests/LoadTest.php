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

use PHPUnit\Framework\Assert;

class LoadTest extends TestCase
{
    public function testLoad(): void
    {
        $client = self::createPantherClient();

        $lastResult = '0';
        $toTest = 500;

        try {
            for ($i = 0; $i < $toTest+1; $i++) {
                $lastResult = $client->request('GET', '/cookie.php')->filter('#barcelona')->text();
            }
        } catch (\Exception $ex) {

        }

        $this->assertSame((string)($toTest), $lastResult);
    }
}
