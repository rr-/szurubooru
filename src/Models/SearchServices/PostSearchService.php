<?php
use \Chibi\Sql as Sql;

class PostSearchService extends AbstractSearchService
{
	public static function getPostIdsAround($searchQuery, $postId)
	{
		return Core::getDatabase()->transaction(function() use ($searchQuery, $postId)
		{
			if (Core::getDatabase()->getDriver() == 'sqlite')
				$stmt = Sql\Statements::raw('CREATE TEMPORARY TABLE IF NOT EXISTS post_search(id INTEGER PRIMARY KEY AUTOINCREMENT, post_id INTEGER)');
			else
				$stmt = Sql\Statements::raw('CREATE TEMPORARY TABLE IF NOT EXISTS post_search(id INTEGER PRIMARY KEY AUTO_INCREMENT, post_id INTEGER)');
			Core::getDatabase()->execute($stmt);

			$stmt = Sql\Statements::delete();
			$stmt->setTable('post_search');
			Core::getDatabase()->execute($stmt);

			$innerStmt = Sql\Statements::select();
			$innerStmt->setColumn('post.id');
			$innerStmt->setTable('post');
			self::decorateParser($innerStmt, $searchQuery);
			$stmt = Sql\Statements::insert();
			$stmt->setTable('post_search');
			$stmt->setSource(['post_id'], $innerStmt);
			Core::getDatabase()->execute($stmt);

			$stmt = Sql\Statements::select();
			$stmt->setTable('post_search');
			$stmt->setColumn('id');
			$stmt->setCriterion(Sql\Functors::equals('post_id', new Sql\Binding($postId)));
			$rowId = Core::getDatabase()->fetchOne($stmt)['id'];

			//it's possible that given post won't show in search results:
			//it can be hidden, it can have prohibited safety etc.
			if (!$rowId)
				return [null, null];

			$rowId = intval($rowId);
			$stmt->setColumn('post_id');

			$stmt->setCriterion(Sql\Functors::equals('id', new Sql\Binding($rowId - 1)));
			$nextPostId = Core::getDatabase()->fetchOne($stmt)['post_id'];

			$stmt->setCriterion(Sql\Functors::equals('id', new Sql\Binding($rowId + 1)));
			$prevPostId = Core::getDatabase()->fetchOne($stmt)['post_id'];

			return [$prevPostId, $nextPostId];
		});
	}
}
