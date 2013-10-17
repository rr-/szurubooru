<?php
class Model_Comment extends RedBean_SimpleModel
{
	public static function locate($key)
	{
		$comment = R::findOne('comment', 'id = ?', [$key]);
		if (!$comment)
			throw new SimpleException('Invalid comment ID "' . $key . '"');
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
