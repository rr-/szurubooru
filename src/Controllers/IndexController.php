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
		$this->context->transport->postCount = R::$f->begin()->select('count(1)')->as('count')->from('post')->get('row')['count'];

		$featuredPostRotationTime = $this->config->misc->featuredPostMaxDays * 24 * 3600;

		$featuredPostId = Model_Property::get(Model_Property::FeaturedPostId);
		$featuredPostUserId = Model_Property::get(Model_Property::FeaturedPostUserId);
		$featuredPostDate = Model_Property::get(Model_Property::FeaturedPostDate);
		if (!$featuredPostId or $featuredPostDate + $featuredPostRotationTime < time())
		{
			$featuredPostId = R::$f->begin()
				->select('id')
				->from('post')
				->where('type = ?')->put(PostType::Image)
				->and('safety = ?')->put(PostSafety::Safe)
				->orderBy('random()')
				->desc()
				->get('row')['id'];
			$featuredPostUserId = null;
			$featuredPostDate = time();
			Model_Property::set(Model_Property::FeaturedPostId, $featuredPostId);
			Model_Property::set(Model_Property::FeaturedPostUserId, $featuredPostUserId);
			Model_Property::set(Model_Property::FeaturedPostDate, $featuredPostDate);
		}

		if ($featuredPostId !== null)
		{
			$featuredPost = Model_Post::locate($featuredPostId);
			R::preload($featuredPost, ['user', 'comment', 'favoritee']);
			$featuredPostUser = R::findOne('user', 'id = ?', [$featuredPostUserId]);
			$this->context->featuredPost = $featuredPost;
			$this->context->featuredPostUser = $featuredPostUser;
			$this->context->featuredPostDate = $featuredPostDate;
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
		$this->context->path = $this->config->help->paths[$tab];
		$this->context->stylesheets []= 'index-help.css';
		$this->context->stylesheets []= 'tabs.css';
		$this->context->subTitle = 'help';
		$this->context->tab = $tab;
	}
}
