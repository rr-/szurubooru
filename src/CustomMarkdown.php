<?php
class CustomMarkdown extends \Michelf\Markdown
{
	public function __construct()
	{
		$this->no_markup = true;
		$this->span_gamut += ['doSpoilers' => 71];
		$this->span_gamut += ['doSpoilers' => 71];
		$this->span_gamut += ['doStrike' => 6];
		$this->span_gamut += ['doPosts' => 8];
		$this->span_gamut += ['doTags' => 9];
		$this->span_gamut += ['doAutoLinks2' => 29];

		//fix italics/bold in the middle of sentence
		$prop = ['em_relist', 'strong_relist', 'em_strong_relist'];
		for ($i = 0; $i < 3; $i ++)
		{
			$this->{$prop[$i]}[''] = '(?:(?<!\*)' . str_repeat('\*', $i + 1) . '(?!\*)|(?<![a-zA-Z0-9_])' . str_repeat('_', $i + 1) . '(?!_))(?=\S|$)(?![\.,:;]\s)';
			$this->{$prop[$i]}[str_repeat('*', $i + 1)] = '(?<=\S|^)(?<!\*)' . str_repeat('\*', $i + 1) . '(?!\*)';
			$this->{$prop[$i]}[str_repeat('_', $i + 1)] = '(?<=\S|^)(?<!_)' . str_repeat('_', $i + 1) . '(?![a-zA-Z0-9_])';
		}

		parent::__construct();
	}

	protected function doAutoLinks2($text)
	{
		$text = preg_replace_callback('{(?<!<)((https?|ftp):[^\'"><\s]+)}i', [&$this, '_doAutoLinks_url_callback'], $text);
		$text = preg_replace_callback('{(?<!\w)(www\.[^\'"><\s]+)}i', [&$this, '_doAutoLinks_url_callback'], $text);
		return $text;
	}

	protected function _doAnchors_inline_callback($matches)
	{
		if ($matches[3] == '')
			$url = &$matches[4];
		else
			$url = &$matches[3];
		if (!preg_match('/^((https?|ftp):|)\/\//', $url))
			$url = 'http://' . $url;
		return parent::_doAnchors_inline_callback($matches);
	}

	protected function doHardBreaks($text)
	{
		return preg_replace_callback('/\n/', [&$this, '_doHardBreaks_callback'], $text);
	}

	protected function doStrike($text)
	{
		return preg_replace_callback('{(~~|---)([^~]+)\1}', function($x)
		{
			return $this->hashPart('<del>') . $x[2] . $this->hashPart('</del>');
		}, $text);
	}

	protected function doSpoilers($text)
	{
		if (is_array($text))
			$text = $this->hashPart('<span class="spoiler">') . $text[1] . $this->hashPart('</span>');
		return preg_replace_callback('{\[spoiler\]((?:[^\[]|\[(?!\/?spoiler\])|(?R))+)\[\/spoiler\]}is', [__CLASS__, 'doSpoilers'], $text);
	}

	protected function doPosts($text)
	{
		return preg_replace_callback('/(?:(?<!\w))@(\d+)/', function($x)
		{
			return $this->hashPart('<a href="' . \Chibi\UrlHelper::route('post', 'view', ['id' => $x[1]]) . '">') . $x[0] . $this->hashPart('</a>');
		}, $text);
	}

	protected function doTags($text)
	{
		return preg_replace_callback('/(?:(?<!\w))#([a-zA-Z0-9_-]+)/', function($x)
		{
			return $this->hashPart('<a href="' . \Chibi\UrlHelper::route('post', 'list', ['query' => $x[1]]) . '">') . $x[0] . $this->hashPart('</a>');
		}, $text);
	}

	protected function doUsers($text)
	{
		return preg_replace_callback('/(?:(?<!\w))\+([a-zA-Z0-9_-]+)/', function($x)
		{
			return $this->hashPart('<a href="' . \Chibi\UrlHelper::route('user', 'view', ['name' => $x[1]]) . '">') . $x[0] . $this->hashPart('</a>');
		}, $text);
	}
}
