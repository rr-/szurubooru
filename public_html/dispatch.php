<?php
chdir('..');
require_once 'src/core.php';
require_once 'src/Bootstrap.php';

$query = $_SERVER['REQUEST_URI'];
\Chibi\Facade::run($query, configFactory(), new Bootstrap());
