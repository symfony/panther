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

if (
    isset($_SERVER['HTTP_CLIENT_IP'])
    || isset($_SERVER['HTTP_X_FORWARDED_FOR'])
    || '127.0.0.1' !== $_SERVER['REMOTE_ADDR']
) {
    header('HTTP/1.0 403 Forbidden');
    exit('You are not allowed to access this file. Check "tests/fixtures/security-check.php" for more information.');
}
