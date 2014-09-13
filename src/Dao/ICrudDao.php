<?php
namespace Szurubooru\Dao;

interface ICrudDao
{
	public function findAll();

	public function findById($objectId);

	public function save(&$object);

	public function deleteById($objectId);

	public function deleteAll();
}
