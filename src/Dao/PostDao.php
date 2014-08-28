<?php
namespace Szurubooru\Dao;

final class PostDao extends AbstractDao implements ICrudDao
{
	public function __construct(\MongoDB $mongoDb)
	{
		parent::__construct($mongoDb, 'posts', '\Szurubooru\Entities\Post');
	}
}
