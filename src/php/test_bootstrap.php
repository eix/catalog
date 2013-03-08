<?php

// Define the current environment as TEST.
$_SERVER['SERVER_ENV'] = 'TEST';

// Bring up dependencies.
require __DIR__ . '/lib/eix-0.1.2-devel.phar';

// Bootstrap the main code.
require __DIR__ . '/bootstrap.php';