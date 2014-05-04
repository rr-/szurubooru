<?php
class CustomMarkdown extends \Michelf\MarkdownExtra
{
	protected $simple = false;

	public function __construct($simple = false)
	{
		$this->simple = $simple;
		$this->no_markup = $simple;
		$this->span_gamut += ['doStrike' => 6];
		$this->span_gamut += ['doUsers' => 7];
		$this->span_gamut += ['doPosts' => 8];
		$this->span_gamut += ['doSpoilers' => 8.5];
		$this->span_gamut += ['doSearchPermalinks' => 8.75];
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

	//make atx-style headers require space after hash
	protected function _doHeaders_callback_atx($matches)
	{
		if (!preg_match('/^#+\s/', $matches[0]))
			return $matches[0];
		return parent::_doHeaders_callback_atx($matches);
	}

	//disable paragraph forming when using simple markdown
	protected function formParagraphs($text)
	{
		if ($this->simple)
		{
			$text = preg_replace('/\A\n+|\n+\z/', '', $text);
			$grafs = preg_split('/\n{2,}/', $text, -1, PREG_SPLIT_NO_EMPTY);
			foreach ($grafs as $key => $value)
			{
				if (!preg_match('/^B\x1A[0-9]+B$/', $value))
				{
					$value = $this->runSpanGamut($value);
					$grafs[$key] = $this->unhash($value);
				}
				else
				{
					$grafs[$key] = $this->html_hashes[$value];
				}
			}
			return implode("\n\n", $grafs);
		}
		return parent::formParagraphs($text);
	}

	public static function simpleTransform($text)
	{
		$parser = new self(true);
		return $parser->transform($text);
	}

	//automatically form links out of http://(...) and www.(...)
	protected function doAutoLinks2($text)
	{
		$text = preg_replace_callback('{(?<!<)((https?|ftp):[^\'"><\s(){}]+)}i', [&$this, '_doAutoLinks_url_callback'], $text);
		$text = preg_replace_callback('{(?<![^\s\(\)\[\]])(www\.[^\'"><\s(){}]+)}i', [&$this, '_doAutoLinks_url_callback'], $text);
		return $text;
	}

	//extend anchors callback for doAutolinks2
	protected function _doAnchors_inline_callback($matches)
	{
		if ($matches[3] == '')
			$url = &$matches[4];
		else
			$url = &$matches[3];
		if (!preg_match('/^((https?|ftp):|)\//', $url))
			$url = 'http://' . $url;
		return parent::_doAnchors_inline_callback($matches);
	}

	//handle white characters inside code blocks
	//so that they won't be optimized away by prettifying HTML
	protected function _doCodeBlocks_callback($matches)
	{
		$codeblock = $matches[1];

		$codeblock = $this->outdent($codeblock);
		$codeblock = htmlspecialchars($codeblock, ENT_NOQUOTES);

		$codeblock = preg_replace('/\A\n+|\n+\z/', '', $codeblock);
		$codeblock = preg_replace('/\n/', '<br/>', $codeblock);
		$codeblock = preg_replace('/\t/', '&tab;', $codeblock);
		$codeblock = preg_replace('/ /', '&nbsp;', $codeblock);

		$codeblock = "<pre><code>$codeblock\n</code></pre>";
		return "\n\n".$this->hashBlock($codeblock)."\n\n";
	}

	//change hard breaks trigger - simple \n followed by text
	//instead of two spaces followed by \n
	protected function doHardBreaks($text)
	{
		return preg_replace_callback('/\n(?=[\[\]\(\)\w])/', [&$this, '_doHardBreaks_callback'], $text);
	}

	protected function doStrike($text)
	{
		return preg_replace_callback('{(~~|---)([^~]+)\1}', function($x)
		{
			return $this->hashPart('<del>' . $x[2] . '</del>');
		}, $text);
	}

	protected function doSpoilers($text)
	{
		if (is_array($text))
			$text = $this->hashBlock('<span class="spoiler">') . $this->runSpanGamut($text[1]) . $this->hashBlock('</span>');
		return preg_replace_callback('{(?<!#)\[spoiler\]((?:[^\[]|\[(?!\/?spoiler\])|(?R))+)\[\/spoiler\]}is', [__CLASS__, 'doSpoilers'], $text);
	}

	protected function doPosts($text)
	{
		$link = \Chibi\Router::linkTo(['PostController', 'genericView'], ['id' => '_post_']);
		return preg_replace_callback('/(?:(?<![^\s\(\)\[\]]))@(\d+)/', function($x) use ($link)
		{
			return $this->hashPart('<a href="' . str_replace('_post_', $x[1], $link) . '"><code>' . $x[0] . '</code></a>');
		}, $text);
	}

	protected function doTags($text)
	{
		$link = \Chibi\Router::linkTo(['PostController', 'listView'], ['query' => '_query_']);
		return preg_replace_callback('/(?:(?<![^\s\(\)\[\]]))#([()\[\]a-zA-Z0-9_.-]+)/', function($x) use ($link)
		{
			return $this->hashPart('<a href="' . str_replace('_query_', $x[1], $link) . '">' . $x[0] . '</a>');
		}, $text);
	}

	protected function doUsers($text)
	{
		$link = \Chibi\Router::linkTo(['UserController', 'genericView'], ['name' => '_name_']);
		return preg_replace_callback('/(?:(?<![^\s\(\)\[\]]))\+([a-zA-Z0-9_-]+)/', function($x) use ($link)
		{
			return $this->hashPart('<a href="' . str_replace('_name_', $x[1], $link) . '">' . $x[0] . '</a>');
		}, $text);
	}

	protected function doSearchPermalinks($text)
	{
		$link = \Chibi\Router::linkTo(['PostController', 'listView'], ['query' => '_query_']);
		return preg_replace_callback('{\[search\]((?:[^\[]|\[(?!\/?search\]))+)\[\/search\]}is', function($x) use ($link)
		{
			return $this->hashPart('<a href="' . str_replace('_query_', urlencode($x[1]), $link) . '">' . $x[1] . '</a>');
		}, $text);
	}
}
