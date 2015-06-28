<?php
namespace Szurubooru\PDOEx;

abstract class BaseQuery implements \IteratorAggregate
{
    const CLAUSE_BASE = 'base';
    const CLAUSE_WHERE = 'where';
    const CLAUSE_JOIN = 'join';
    const CLAUSE_ORDER = 'order';
    const CLAUSE_GROUP = 'group';
    const CLAUSE_LIMIT = 'limit';

    protected $table;
    protected $pdoex;

    protected $clauses;
    protected $parameters;

    public function __construct(PDOEx $pdoex, $table)
    {
        $this->pdoex = $pdoex;
        $this->table = $table;
        $this->clauses = [];
        $this->parameters = [];
        $this->init();
    }

    public function getIterator()
    {
        return $this->execute();
    }

    public function getQuery()
    {
        return $this->buildQuery();
    }

    public function execute()
    {
        $query = $this->buildQuery();
        $parameters = $this->getParameters();

        try
        {
            $result = $this->pdoex->prepare($query);
            if (!$result || !$result->execute($parameters))
                $this->throwQueryException($query, $parameters, 'unknown reason');
        }
        catch (\PDOException $e)
        {
            $this->throwQueryException($query, $parameters, $e->getMessage());
        }
        return $result;
    }

    public function innerJoin($table, $condition)
    {
        if (!isset($this->clauses[self::CLAUSE_JOIN]))
            $this->clauses[self::CLAUSE_JOIN] = [];

        $this->clauses[self::CLAUSE_JOIN][] = 'INNER JOIN ' . $table . ' ON ' . $condition;
        return $this;
    }

    public function where($key, $value = null)
    {
        if ($key === null)
        {
            $this->clauses[self::CLAUSE_WHERE] = [];
            return;
        }

        if (!isset($this->clauses[self::CLAUSE_WHERE]))
            $this->clauses[self::CLAUSE_WHERE] = [];

        $sql = empty($this->clauses[self::CLAUSE_WHERE]) ? 'WHERE' : 'AND';

        if (strpos($key, '?') !== false)
        {
            if (!is_array($value))
                $value = [$value];
            assert(substr_count($key, '?') === count($value), 'Binding ' .  print_r($value, true) . ' to ' . $key);
            $index = 0;
            $sql .= ' ' . preg_replace_callback(
                '/\?/',
                function($match) use (&$index, $value)
                {
                    $ret = $this->bind($value[$index]);
                    $index ++;
                    return $ret;
                },
                $key);
            $this->clauses[self::CLAUSE_WHERE][] = $sql;
            return $this;
        }

        if (!is_array($value))
        {
            if ($value === null)
                $sql .= ' ' . $key . ' IS NULL';
            else
                $sql .= ' ' . $key . ' = ' . $this->bind($value);
            $this->clauses[self::CLAUSE_WHERE][] = $sql;
            return $this;
        }

        if (empty($value))
        {
            $sql .= ' 0';
            $this->clauses[self::CLAUSE_WHERE][] = $sql;
            return $this;
        }

        $sql .= ' ' . $key . ' IN (';
        foreach ($value as $id => $val)
        {
            $sql .= $this->bind($val) . ', ';
        }
        $sql = substr($sql, 0, -2);
        $sql .= ')';
        $this->clauses[self::CLAUSE_WHERE][] = $sql;
        return $this;
    }

    protected function getParameters()
    {
        return $this->parameters;
    }

    protected function buildQuery()
    {
        $priorities = array_flip([
            self::CLAUSE_BASE,
            self::CLAUSE_JOIN,
            self::CLAUSE_WHERE,
            self::CLAUSE_GROUP,
            self::CLAUSE_ORDER,
            self::CLAUSE_LIMIT]);
        uksort(
            $this->clauses,
            function($clauseNameA, $clauseNameB) use ($priorities)
            {
                return $priorities[$clauseNameA] - $priorities[$clauseNameB];
            });

        $query = '';
        foreach ($this->clauses as $statements)
        {
            if (!is_array($statements))
                $statements = [$statements];
            foreach ($statements as $statement)
                $query .= ' ' . $statement;
        }
        return trim($query);
    }

    protected abstract function init();

    protected function bind($value)
    {
        $id = ':' . uniqid();
        $this->parameters[$id] = $value;
        return $id;
    }

    private function throwQueryException($query, $parameters, $message)
    {
        throw new \Exception(sprintf(
            'Problem executing query "%s" with parameters %s: %s',
            $query,
            print_r($parameters, true),
            $message));
    }
}
