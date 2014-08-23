<?php
class Assets extends \Chibi\Util\Assets
{
	private $pageThumbnail = null;
	private $subTitle = null;
	private $engineVersion = null;

	public function __construct()
	{
		$this->engineVersion = PropertyModel::get(PropertyModel::EngineVersion);
	}

	public function setSubTitle($text)
	{
		$this->subTitle = $text;
	}

	public function setPageThumbnail($path)
	{
		$this->pageThumbnail = $path;
	}

	public function addStylesheet($path)
	{
		return parent::addStylesheet('/media/css/' . $path . '?' . $this->engineVersion);
	}

	public function addStylesheetFullPath($path)
	{
		return parent::addStylesheet($path);
	}

	public function addScript($path)
	{
		return $this->addScriptFullPath('/media/js/' . $path . '?' . $this->engineVersion);
	}

	public function addScriptFullPath($path)
	{
		return parent::addScript($path);
	}

	public function transformHtml($html)
	{
		$this->title = isset($this->subTitle)
			? sprintf('%s&nbsp;&ndash;&nbsp;%s', $this->title, $this->subTitle)
			: $this->title;

		$html = parent::transformHtml($html);

		$headSnippet = '<meta property="og:title" content="' . $this->title . '"/>';
		$headSnippet .= '<meta property="og:url" content="' . \Chibi\Util\Url::currentUrl() . '"/>';
		if (!empty($this->pageThumbnail))
			$headSnippet .= '<meta property="og:image" content="' . $this->pageThumbnail . '"/>';

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
