<?php
namespace Szurubooru\Dao;

interface IFileDao
{
	public function load($fileName);

	public function save($fileName, $contents);

	public function delete($fileName);

	public function exists($fileName);
}
