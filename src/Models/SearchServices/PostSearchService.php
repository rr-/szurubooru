<?php
use \Chibi\Sql as Sql;
use \Chibi\Database as Database;

class PostSearchService extends AbstractSearchService
{
	public static function getPostIdsAround($searchQuery, $postId)
	{
		return Database::transaction(function() use ($searchQuery, $postId)
		{
			if (Database::getDriver() == 'sqlite')
				$stmt = new Sql\RawStatement('CREATE TEMPORARY TABLE IF NOT EXISTS post_search(id INTEGER PRIMARY KEY AUTOINCREMENT, post_id INTEGER)');
			else
				$stmt = new Sql\RawStatement('CREATE TEMPORARY TABLE IF NOT EXISTS post_search(id INTEGER PRIMARY KEY AUTO_INCREMENT, post_id INTEGER)');
			Database::exec($stmt);

			$stmt = new Sql\DeleteStatement();
			$stmt->setTable('post_search');
			Database::exec($stmt);

			$innerStmt = new Sql\SelectStatement($searchQuery);
			$innerStmt->setColumn('post.id');
			$innerStmt->setTable('post');
			self::decorateParser($innerStmt, $searchQuery);
			$stmt = new Sql\InsertStatement();
			$stmt->setTable('post_search');
			$stmt->setSource(['post_id'], $innerStmt);
			Database::exec($stmt);

			$stmt = new Sql\SelectStatement();
			$stmt->setTable('post_search');
			$stmt->setColumn('id');
			$stmt->setCriterion(new Sql\EqualsFunctor('post_id', new Sql\Binding($postId)));
			$rowId = Database::fetchOne($stmt)['id'];

			//it's possible that given post won't show in search results:
			//it can be hidden, it can have prohibited safety etc.
			if (!$rowId)
				return [null, null];

			$rowId = intval($rowId);
			$stmt->setColumn('post_id');

			$stmt->setCriterion(new Sql\EqualsFunctor('id', new Sql\Binding($rowId - 1)));
			$nextPostId = Database::fetchOne($stmt)['post_id'];

			$stmt->setCriterion(new Sql\EqualsFunctor('id', new Sql\Binding($rowId + 1)));
			$prevPostId = Database::fetchOne($stmt)['post_id'];

			return [$prevPostId, $nextPostId];
		});
	}
}
