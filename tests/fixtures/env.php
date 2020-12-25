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

require __DIR__.'/security-check.php';

if ('APP_ENV' === $_GET['name'] ?? null) { ?>
    <?php echo $_ENV['APP_ENV']; ?>
<?php } else { ?>
    <?php echo $_ENV['FOO']; ?>
<?php } ?>
