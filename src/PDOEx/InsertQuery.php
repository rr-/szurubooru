<?php
namespace Szurubooru\PDOEx;

class InsertQuery extends BaseQuery
{
    private $values = [];

    public function values(array $values)
    {
        $this->values = $values;
        $this->refreshBaseQuery();
        return $this;
    }

    public function where($key, $value = null)
    {
        throw new \BadMethodCallException('This makes no sense!');
    }

    public function innerJoin($table, $condition)
    {
        throw new \BadMethodCallException('This makes no sense!');
    }

    protected function init()
    {
        $this->refreshBaseQuery();
    }

    private function refreshBaseQuery()
    {
        $sql = 'INSERT INTO ' . $this->table;
        $sql .= ' (' . implode(', ', array_keys($this->values)) . ')';
        $sql .= ' VALUES (';
        foreach ($this->values as $value)
            $sql .= $this->bind($value) . ', ';
        $sql = substr($sql, 0, -2);
        $sql .= ')';
        $this->clauses[self::CLAUSE_BASE] = $sql;
    }
}
