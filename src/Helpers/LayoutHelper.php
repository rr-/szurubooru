<?php
class LayoutHelper
{
	private static $stylesheets = [];
	private static $scripts = [];
	private static $title = null;
	private static $pageThumb = null;
	private static $subTitle = null;

	public static function setTitle($text)
	{
		self::$title = $text;
	}

	public static function setSubTitle($text)
	{
		self::$subTitle = $text;
	}

	public static function setPageThumb($path)
	{
		self::$pageThumb = $path;
	}

	public static function addStylesheet($css)
	{
		self::$stylesheets []= $css;
	}

	public static function addScript($js)
	{
		self::$scripts []= $js;
	}

	public static function transformHtml($html)
	{
		$bodySnippet = '';
		$headSnippet = '';

		$title = isset(self::$subTitle)
			? sprintf('%s&nbsp;&ndash;&nbsp;%s', self::$title, self::$subTitle)
			: self::$title;
		$headSnippet .= '<title>' . $title . '</title>';

		$headSnippet .= '<meta property="og:title" content="' . $title . '"/>';
		$headSnippet .= '<meta property="og:url" content="' . \Chibi\UrlHelper::currentUrl() . '"/>';
		if (!empty(self::$pageThumb))
			$headSnippet .= '<meta property="og:image" content="' . self::$pageThumb . '"/>';

		foreach (array_unique(self::$stylesheets) as $name)
			$headSnippet .= '<link rel="stylesheet" type="text/css" href="' . \Chibi\UrlHelper::absoluteUrl('/media/css/' . $name) . '"/>';

		foreach (array_unique(self::$scripts) as $name)
			$bodySnippet .= '<script type="text/javascript" src="' . \Chibi\UrlHelper::absoluteUrl('/media/js/' . $name)  . '"></script>';

		$bodySnippet .= '<script type="text/javascript">';
		$bodySnippet .= '$(function() {';
		$bodySnippet .= '$(\'body\').trigger(\'dom-update\');';
		$bodySnippet .= '});';
		$bodySnippet .= '</script>';

		$html = str_replace('</head>', $headSnippet . '</head>', $html);
		$html = str_replace('</body>', $bodySnippet . '</body>', $html);
		return $html;
	}
}
