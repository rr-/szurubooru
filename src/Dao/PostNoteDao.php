<?php
namespace Szurubooru\Dao;
use Szurubooru\Dao\EntityConverters\PostNoteEntityConverter;
use Szurubooru\DatabaseConnection;

class PostNoteDao extends AbstractDao implements ICrudDao
{
	public function __construct(DatabaseConnection $databaseConnection)
	{
		parent::__construct(
			$databaseConnection,
			'postNotes',
			new PostNoteEntityConverter());
	}
}
