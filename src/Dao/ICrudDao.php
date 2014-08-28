<?php
namespace Szurubooru\Dao;

interface ICrudDao
{
	public function getAll();

	public function getById($objectId);

	public function save(&$object);

	public function deleteById($objectId);

	public function deleteAll();
}
