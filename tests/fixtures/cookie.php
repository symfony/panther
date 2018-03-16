<?php declare(strict_types=1);
$val = $_COOKIE['barcelona'] ?? 0;

\setcookie('barcelona', (string) ($val + 1), 0, '/cookie.php', '127.0.0.1', false, true);

?>

<!DOCTYPE html>
<html lang="en">
<body>
    <div id="barcelona"><?=$val; ?></div>
    <div id="foo"><?=$_COOKIE['foo'] ?? ''; ?></div>
</body>
</html>

