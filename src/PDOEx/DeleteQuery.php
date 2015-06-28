<?php
namespace Szurubooru\PDOEx;

class DeleteQuery extends BaseQuery
{
    protected function init()
    {
        $this->clauses[self::CLAUSE_BASE] = 'DELETE FROM ' . $this->table;
    }
}
