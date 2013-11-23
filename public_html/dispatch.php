<?php
require_once '../src/core.php';

$query = $_SERVER['REQUEST_URI'];
\Chibi\Facade::run($query, new Bootstrap());
