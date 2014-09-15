<?php
namespace Szurubooru\Dao;

final class PostDao extends AbstractDao implements ICrudDao
{
	public function __construct(
		\Szurubooru\DatabaseConnection $databaseConnection)
	{
		parent::__construct(
			$databaseConnection,
			'posts',
			new \Szurubooru\Dao\EntityConverters\PostEntityConverter());
	}
}
