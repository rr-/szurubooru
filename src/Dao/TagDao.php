<?php
namespace Szurubooru\Dao;

class TagDao extends AbstractDao implements ICrudDao
{
	public function __construct(\Szurubooru\DatabaseConnection $databaseConnection)
	{
		parent::__construct(
			$databaseConnection,
			'tags',
			new \Szurubooru\Dao\EntityConverters\TagEntityConverter());
	}
}
