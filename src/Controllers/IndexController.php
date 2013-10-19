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

		$featuredPostRotationTime = $this->config->main->featuredPostMaxDays * 24 * 3600;

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
		}
	}

	/**
	* @route /help
	*/
	public function helpAction()
	{
		$this->context->subTitle = 'help';
	}
}
