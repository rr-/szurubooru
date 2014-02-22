<?php
class IndexController
{
	/**
	* @route /
	* @route /index
	*/
	public function indexAction()
	{
		$this->context->transport->postCount = PostModel::getCount();

		$featuredPost = $this->getFeaturedPost();
		if ($featuredPost)
		{
			$this->context->featuredPost = $featuredPost;
			$this->context->featuredPostDate = PropertyModel::get(PropertyModel::FeaturedPostDate);
			$this->context->featuredPostUser = UserModel::findByNameOrEmail(PropertyModel::get(PropertyModel::FeaturedPostUserName), false);
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
		$this->context->tab = $tab;
	}

	private function getFeaturedPost()
	{
		$featuredPostRotationTime = $this->config->misc->featuredPostMaxDays * 24 * 3600;

		$featuredPostId = PropertyModel::get(PropertyModel::FeaturedPostId);
		$featuredPostDate = PropertyModel::get(PropertyModel::FeaturedPostDate);

		//check if too old
		if (!$featuredPostId or $featuredPostDate + $featuredPostRotationTime < time())
			return $this->featureNewPost();

		//check if post was deleted
		$featuredPost = PostModel::findById($featuredPostId, false);
		if (!$featuredPost)
			return $this->featureNewPost();

		return $featuredPost;
	}

	private function featureNewPost()
	{
		$stmt = (new SqlSelectStatement)
			->setColumn('id')
			->setTable('post')
			->setCriterion((new SqlConjunction)
				->add(new SqlEqualsOperator('type', new SqlBinding(PostType::Image)))
				->add(new SqlEqualsOperator('safety', new SqlBinding(PostSafety::Safe))))
			->setOrderBy(new SqlRandomOperator(), SqlSelectStatement::ORDER_DESC);
		$featuredPostId = Database::fetchOne($stmt)['id'];
		if (!$featuredPostId)
			return null;

		PropertyModel::set(PropertyModel::FeaturedPostId, $featuredPostId);
		PropertyModel::set(PropertyModel::FeaturedPostDate, time());
		PropertyModel::set(PropertyModel::FeaturedPostUserName, null);
		return PostModel::findById($featuredPostId);
	}
}
