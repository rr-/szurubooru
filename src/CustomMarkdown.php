<?php
class CustomMarkdown extends \Michelf\Markdown
{
	public function __construct()
	{
		$this->no_markup = true;
		$this->span_gamut += ['doSpoilers' => 71];
		$this->span_gamut += ['doPosts' => 8];
		$this->span_gamut += ['doTags' => 9];
		parent::__construct();
	}

	protected function doHardBreaks($text)
	{
		return preg_replace_callback('/\n/', array(&$this, '_doHardBreaks_callback'), $text);
	}

	protected function doSpoilers($text)
	{
		if (is_array($text))
		{
			$text = $this->hashPart('<span class="spoiler">') . $text[1] . $this->hashPart('</span>');
		}
		return preg_replace_callback('{\[spoiler\]((?:[^\[]|\[(?!\/?spoiler\])|(?R))+)\[\/spoiler\]}is', [__CLASS__, 'doSpoilers'], $text);
	}

	protected function doPosts($text)
	{
		return preg_replace_callback('/@(\d+)/', function($x)
		{
			return $this->hashPart('<a href="' . \Chibi\UrlHelper::route('post', 'view', ['id' => $x[1]]) . '">') . $x[0] . $this->hashPart('</a>');
		}, $text);
	}

	protected function doTags($text)
	{
		return preg_replace_callback('/#([a-zA-Z0-9_-]+)/', function($x)
		{
			return $this->hashPart('<a href="' . \Chibi\UrlHelper::route('post', 'list', ['query' => $x[1]]) . '">') . $x[0] . $this->hashPart('</a>');
		}, $text);
	}
}
