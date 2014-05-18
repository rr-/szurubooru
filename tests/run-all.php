<?php
require_once __DIR__ . '/../src/core.php';
\Chibi\Autoloader::registerFileSystem(__DIR__);

$runner = new SzurubooruTestRunner();
$runner->run();
