<?php
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

			$query = (new SqlQuery)
				->update('comment')
				->set(join(', ', array_map(function($key) { return $key . ' = ?'; }, array_keys($bindings))))
				->put(array_values($bindings))
				->where('id = ?')->put($comment->id);

			Database::query($query);
		});
	}

	public static function remove($comment)
	{
		Database::transaction(function() use ($comment)
		{
			$query = (new SqlQuery)
				->deleteFrom('comment')
				->where('id = ?')->put($comment->id);
			Database::query($query);
		});
	}



	public static function findAllByPostId($key)
	{
		$query = new SqlQuery();
		$query
			->select('comment.*')
			->from('comment')
			->where('post_id = ?')
			->put($key);

		$rows = Database::fetchAll($query);
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
		$config = \Chibi\Registry::getConfig();

		if (strlen($text) < $config->comments->minLength)
			throw new SimpleException(sprintf('Comment must have at least %d characters', $config->comments->minLength));

		if (strlen($text) > $config->comments->maxLength)
			throw new SimpleException(sprintf('Comment must have at most %d characters', $config->comments->maxLength));

		return $text;
	}
}
