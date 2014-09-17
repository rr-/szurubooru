<?php
namespace Szurubooru\Dao\Services;

class PostSearchService extends AbstractSearchService
{
	public function __construct(
		\Szurubooru\DatabaseConnection $databaseConnection,
		\Szurubooru\Dao\PostDao $postDao)
	{
		parent::__construct($databaseConnection, $postDao);
	}
}
