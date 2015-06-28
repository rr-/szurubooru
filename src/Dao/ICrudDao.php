<?php
namespace Szurubooru\Dao;

interface ICrudDao
{
    public function findById($objectId);

    public function save(&$object);

    public function deleteById($objectId);
}
