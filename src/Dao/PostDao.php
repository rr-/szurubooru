<?php
namespace Szurubooru\Dao;

final class PostDao extends AbstractDao implements ICrudDao
{
	public function __construct(\Szurubooru\Config $config)
	{
		parent::__construct($config, 'posts', '\Szurubooru\Entities\Post');
	}
}
