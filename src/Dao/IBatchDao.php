<?php
namespace Szurubooru\Dao;

interface IBatchDao
{
	public function findAll();

	public function deleteAll();

	public function batchSave(array $objects);
}
