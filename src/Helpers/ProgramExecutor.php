<?php
namespace Szurubooru\Helpers;

class ProgramExecutor
{
    public static function run($programName, $arguments)
    {
        $quotedArguments = array_map('escapeshellarg', $arguments);

        $cmd = sprintf('%s %s 2>&1', $programName, implode(' ', $quotedArguments));
        return exec($cmd);
    }

    public static function isProgramAvailable($programName)
    {
        if (PHP_OS === 'WINNT')
        {
            exec('where "' . $programName . '" 2>&1 >nul', $trash, $exitCode);
        }
        else
        {
            exec('command -v "' . $programName . '" >/dev/null 2>&1', $trash, $exitCode);
        }
        return $exitCode === 0;
    }
}
