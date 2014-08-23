<?php
require_once 'SzurubooruTestRunner.php';
$runner = new SzurubooruTestRunner();
$success = $runner->run();
exit($success ? 0 : 1);
