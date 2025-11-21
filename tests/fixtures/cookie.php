<?php

/*
 * This file is part of the Panther project.
 *
 * (c) Kévin Dunglas <kevin@dunglas.dev>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

require __DIR__.'/security-check.php';

$val = $_COOKIE['barcelona'] ?? 0;

setcookie('barcelona', (string) ($val + 1), 0, '/cookie.php', '127.0.0.1', false, true);
?>
<!DOCTYPE html>
<html lang="en">
<body>
    <div id="barcelona"><?php echo $val; ?></div>
    <div id="foo"><?php echo $_COOKIE['foo'] ?? ''; ?></div>
</body>
</html>
