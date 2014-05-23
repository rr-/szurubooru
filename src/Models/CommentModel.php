<?php
use \Chibi\Sql as Sql;

final class CommentModel extends AbstractCrudModel
{
	public static function getTableName()
	{
		return 'comment';
	}

	protected static function saveSingle($comment)
	{
		$comment->validate();
		$comment->getPost()->removeCache('comment_count');

		Core::getDatabase()->transaction(function() use ($comment)
		{
			self::forgeId($comment);

			$bindings = [
				'text' => $comment->getText(),
				'post_id' => $comment->getPostId(),
				'comment_date' => $comment->getCreationTime(),
				'commenter_id' => $comment->getCommenterId()];

			$stmt = Sql\Statements::update();
			$stmt->setTable('comment');
			$stmt->setCriterion(Sql\Functors::equals('id', new Sql\Binding($comment->getId())));

			foreach ($bindings as $key => $val)
				$stmt->setColumn($key, new Sql\Binding($val));

			Core::getDatabase()->execute($stmt);
		});

		return $comment;
	}

	protected static function removeSingle($comment)
	{
		Core::getDatabase()->transaction(function() use ($comment)
		{
			$comment->getPost()->removeCache('comment_count');
			$stmt = Sql\Statements::delete();
			$stmt->setTable('comment');
			$stmt->setCriterion(Sql\Functors::equals('id', new Sql\Binding($comment->getId())));
			Core::getDatabase()->execute($stmt);
		});
	}



	public static function getAllByPostId($key)
	{
		$stmt = Sql\Statements::select();
		$stmt->setColumn('comment.*');
		$stmt->setTable('comment');
		$stmt->setCriterion(Sql\Functors::equals('post_id', new Sql\Binding($key)));

		$rows = Core::getDatabase()->fetchAll($stmt);
		if ($rows)
			return self::spawnFromDatabaseRows($rows);
		return [];
	}



	public static function preloadCommenters($comments)
	{
		self::preloadOneToMany($comments,
			function($comment) { return $comment->getCommenterId(); },
			function($user) { return $user->getId(); },
			function($userIds) { return UserModel::getAllByIds($userIds); },
			function($comment, $user) { return $comment->setCache('commenter', $user); });
	}

	public static function preloadPosts($comments)
	{
		self::preloadOneToMany($comments,
			function($comment) { return $comment->getPostId(); },
			function($post) { return $post->getId(); },
			function($postIds) { return PostModel::getAllByIds($postIds); },
			function($comment, $post) { $comment->setCache('post', $post); });
	}
}
