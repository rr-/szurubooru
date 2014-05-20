<?php
class AbstractController
{
	protected $assets;
	private $layoutName;

	public function isAjax()
	{
		return isset($_SERVER['HTTP_X_AJAX']);
	}

	public function __construct()
	{
		$this->switchLayout('layout-normal');

		$this->assets = new Assets();
		$this->assets->setTitle(Core::getConfig()->main->title);
	}

	public function renderAjax()
	{
		$this->switchLayout('layout-json');
		$this->renderView(null);
	}

	public function renderFile()
	{
		$this->switchLayout('layout-file');
		$this->renderView(null);
	}

	public function renderView($viewName)
	{
		$context = Core::getContext();
		if ($viewName !== null)
			$context->viewName = $viewName;
		View::renderTopLevel($this->layoutName, $this->assets);
	}


	protected function redirectToLastVisitedUrl($filter = null)
	{
		$targetUrl = SessionHelper::getLastVisitedUrl($filter);
		if (!$targetUrl)
			$targetUrl = \Chibi\Router::linkTo(['StaticPagesController', 'mainPageView']);
		$this->redirect($targetUrl);
	}

	protected function redirect($url)
	{
		if ($this->isAjax())
		{
			Core::getContext()->transport->redirectUrl = $url;
			$this->renderAjax();
		}
		else
			\Chibi\Util\Url::forward($url);
	}


	private function switchLayout($layoutName)
	{
		$this->layoutName = $layoutName;
	}
}
