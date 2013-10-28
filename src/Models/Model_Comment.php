<?php
class Model_Comment extends AbstractModel
{
	public static function getTableName()
	{
		return 'comment';
	}

	public static function getQueryBuilder()
	{
		return 'Model_Comment_QueryBuilder';
	}

	public static function locate($key, $throw = true)
	{
		$comment = R::findOne(self::getTableName(), 'id = ?', [$key]);
		if (!$comment)
		{
			if ($throw)
				throw new SimpleException('Invalid comment ID "' . $key . '"');
			return null;
		}
		return $comment;
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

	public function getText()
	{
		return TextHelper::parseMarkdown($this->text);
	}
}
