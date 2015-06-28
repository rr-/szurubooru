#!/usr/bin/php
<?php
require_once(__DIR__
    . DIRECTORY_SEPARATOR . '..'
    . DIRECTORY_SEPARATOR . 'src'
    . DIRECTORY_SEPARATOR . 'Bootstrap.php');

use Szurubooru\Injector;
use Szurubooru\Services\EmailService;

if (!isset($argv[1]))
{
    echo "No recipient email specified.";
    return;
}
$address = $argv[1];

$emailService = Injector::get(EmailService::class);
$emailService->sendEmail($address, 'test', "test\nąćęłóńśźż\n←↑→↓");
