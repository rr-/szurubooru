<?php
class CustomAssetViewDecorator extends \Chibi\AssetViewDecorator
{
	private static $pageThumb = null;
	private static $subTitle = null;

	public static function setSubTitle($text)
	{
		self::$subTitle = $text;
	}

	public static function setPageThumb($path)
	{
		self::$pageThumb = $path;
	}

	public function transformHtml($html)
	{
		self::$title = isset(self::$subTitle)
			? sprintf('%s&nbsp;&ndash;&nbsp;%s', self::$title, self::$subTitle)
			: self::$title;

		$html = parent::transformHtml($html);

		$headSnippet = '<meta property="og:title" content="' . self::$title . '"/>';
		$headSnippet .= '<meta property="og:url" content="' . \Chibi\UrlHelper::currentUrl() . '"/>';
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
