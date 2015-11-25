<?php
namespace Szurubooru\Tests;
use Szurubooru\Dao\TransactionManager;

final class TransactionManagerMock extends TransactionManager
{
    public function rollback($callback)
    {
        return $callback();
    }

    public function commit($callback)
    {
        return $callback();
    }
}
