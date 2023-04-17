<?php

define('START', microtime(1));

require_once __DIR__ . '/../lib/app.php';

$app = new app();
$app->run();
