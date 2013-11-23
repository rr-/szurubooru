<?php
class IndexController
{
	/**
	* @route /
	* @route /index
	*/
	public function indexAction()
	{
		$this->context->subTitle = 'home';
		$this->context->stylesheets []= 'index-index.css';
		$this->context->transport->postCount = Model_Post::getAllPostCount();

		$featuredPost = $this->getFeaturedPost();
		if ($featuredPost)
		{
			$this->context->featuredPost = $featuredPost;
			$this->context->featuredPostDate = Model_Property::get(Model_Property::FeaturedPostDate);
			$this->context->featuredPostUser = Model_User::locate(Model_Property::get(Model_Property::FeaturedPostUserName), false);
			$this->context->pageThumb = \Chibi\UrlHelper::route('post', 'thumb', ['name' => $featuredPost->name]);
		}
	}

	/**
	* @route /help
	* @route /help/{tab}
	*/
	public function helpAction($tab = null)
	{
		if (empty($this->config->help->paths) or empty($this->config->help->title))
			throw new SimpleException('Help is disabled');
		$tab = $tab ?: array_keys($this->config->help->subTitles)[0];
		if (!isset($this->config->help->paths[$tab]))
			throw new SimpleException('Invalid tab');
		$this->context->path = TextHelper::absolutePath($this->config->help->paths[$tab]);
		$this->context->stylesheets []= 'index-help.css';
		$this->context->stylesheets []= 'tabs.css';
		$this->context->subTitle = 'help';
		$this->context->tab = $tab;
	}

	private function getFeaturedPost()
	{
		$featuredPostRotationTime = $this->config->misc->featuredPostMaxDays * 24 * 3600;

		$featuredPostId = Model_Property::get(Model_Property::FeaturedPostId);
		$featuredPostDate = Model_Property::get(Model_Property::FeaturedPostDate);

		//check if too old
		if (!$featuredPostId or $featuredPostDate + $featuredPostRotationTime < time())
			return $this->featureNewPost();

		//check if post was deleted
		$featuredPost = Model_Post::locate($featuredPostId, false, false);
		if (!$featuredPost)
			return $this->featureNewPost();

		return $featuredPost;
	}

	private function featureNewPost()
	{
		$featuredPostId = R::$f->begin()
			->select('id')
			->from('post')
			->where('type = ?')->put(PostType::Image)
			->and('safety = ?')->put(PostSafety::Safe)
			->orderBy('random()')
			->desc()
			->get('row')['id'];
		if (!$featuredPostId)
			return null;

		Model_Property::set(Model_Property::FeaturedPostId, $featuredPostId);
		Model_Property::set(Model_Property::FeaturedPostDate, time());
		Model_Property::set(Model_Property::FeaturedPostUserName, null);
		return Model_Post::locate($featuredPostId);
	}
}
