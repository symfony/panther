<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>User-Agent</title>
</head>
<body>
<?php echo htmlspecialchars($_SERVER['HTTP_USER_AGENT'] ?? ''); ?>
</body>
</html>
