<?php
use \Chibi\Sql as Sql;
use \Chibi\Database as Database;

class CommentModel extends AbstractCrudModel
{
	public static function getTableName()
	{
		return 'comment';
	}

	public static function spawn()
	{
		$comment = new CommentEntity;
		$comment->commentDate = time();
		return $comment;
	}

	public static function save($comment)
	{
		Database::transaction(function() use ($comment)
		{
			self::forgeId($comment);

			$bindings = [
				'text' => $comment->text,
				'post_id' => $comment->postId,
				'comment_date' => $comment->commentDate,
				'commenter_id' => $comment->commenterId];

			$stmt = new Sql\UpdateStatement();
			$stmt->setTable('comment');
			$stmt->setCriterion(new Sql\EqualsFunctor('id', new Sql\Binding($comment->id)));

			foreach ($bindings as $key => $val)
				$stmt->setColumn($key, new Sql\Binding($val));

			Database::exec($stmt);
		});
	}

	public static function remove($comment)
	{
		Database::transaction(function() use ($comment)
		{
			$stmt = new Sql\DeleteStatement();
			$stmt->setTable('comment');
			$stmt->setCriterion(new Sql\EqualsFunctor('id', new Sql\Binding($comment->id)));
			Database::exec($stmt);
		});
	}



	public static function findAllByPostId($key)
	{
		$stmt = new Sql\SelectStatement();
		$stmt->setColumn('comment.*');
		$stmt->setTable('comment');
		$stmt->setCriterion(new Sql\EqualsFunctor('post_id', new Sql\Binding($key)));

		$rows = Database::fetchAll($stmt);
		if ($rows)
			return self::convertRows($rows);
		return [];
	}



	public static function preloadCommenters($comments)
	{
		self::preloadOneToMany($comments,
			function($comment) { return $comment->commenterId; },
			function($user) { return $user->id; },
			function($userIds) { return UserModel::findByIds($userIds); },
			function($comment, $user) { return $comment->setCache('commenter', $user); });
	}

	public static function preloadPosts($comments)
	{
		self::preloadOneToMany($comments,
			function($comment) { return $comment->postId; },
			function($post) { return $post->id; },
			function($postIds) { return PostModel::findByIds($postIds); },
			function($comment, $post) { $comment->setCache('post', $post); });
	}



	public static function validateText($text)
	{
		$text = trim($text);
		$config = getConfig();

		if (strlen($text) < $config->comments->minLength)
			throw new SimpleException('Comment must have at least %d characters', $config->comments->minLength);

		if (strlen($text) > $config->comments->maxLength)
			throw new SimpleException('Comment must have at most %d characters', $config->comments->maxLength);

		return $text;
	}
}
