<?php
class Assets extends \Chibi\Util\Assets
{
	private static $pageThumb = null;
	private static $subTitle = null;

	public static function init()
	{
		\Chibi\Util\Assets::disable();
		self::enable();
	}

	public static function setSubTitle($text)
	{
		self::$subTitle = $text;
	}

	public static function setPageThumb($path)
	{
		self::$pageThumb = $path;
	}

	public static function addStylesheet($path)
	{
		return parent::addStylesheet('/media/css/' . $path);
	}

	public static function addScript($path)
	{
		return parent::addScript('/media/js/' . $path);
	}

	public static function transformHtml($html)
	{
		self::$title = isset(self::$subTitle)
			? sprintf('%s&nbsp;&ndash;&nbsp;%s', self::$title, self::$subTitle)
			: self::$title;

		$html = parent::transformHtml($html);

		$headSnippet = '<meta property="og:title" content="' . self::$title . '"/>';
		$headSnippet .= '<meta property="og:url" content="' . \Chibi\Util\Url::currentUrl() . '"/>';
		if (!empty(self::$pageThumb))
			$headSnippet .= '<meta property="og:image" content="' . self::$pageThumb . '"/>';

		$bodySnippet = '<script type="text/javascript">';
		$bodySnippet .= '$(function() {';
		$bodySnippet .= '$(\'body\').trigger(\'dom-update\');';
		$bodySnippet .= '});';
		$bodySnippet .= '</script>';

		$html = str_replace('</head>', $headSnippet . '</head>', $html);
		$html = str_replace('</body>', $bodySnippet . '</body>', $html);
		return $html;
	}
}

Assets::init();
