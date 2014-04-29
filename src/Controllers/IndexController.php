<?php
class IndexController
{
	public function indexAction()
	{
		$context = getContext();
		$context->transport->postCount = PostModel::getCount();

		$featuredPost = $this->getFeaturedPost();
		if ($featuredPost)
		{
			$context->featuredPost = $featuredPost;
			$context->featuredPostDate = PropertyModel::get(PropertyModel::FeaturedPostDate);
			$context->featuredPostUser = UserModel::findByNameOrEmail(
				PropertyModel::get(PropertyModel::FeaturedPostUserName),
				false);
		}
	}

	public function helpAction($tab = null)
	{
		$config = getConfig();
		$context = getContext();
		if (empty($config->help->paths) or empty($config->help->title))
			throw new SimpleException('Help is disabled');
		$tab = $tab ?: array_keys($config->help->subTitles)[0];
		if (!isset($config->help->paths[$tab]))
			throw new SimpleException('Invalid tab');
		$context->path = TextHelper::absolutePath($config->help->paths[$tab]);
		$context->tab = $tab;
	}

	private function getFeaturedPost()
	{
		$config = getConfig();
		$featuredPostRotationTime = $config->misc->featuredPostMaxDays * 24 * 3600;

		$featuredPostId = PropertyModel::get(PropertyModel::FeaturedPostId);
		$featuredPostDate = PropertyModel::get(PropertyModel::FeaturedPostDate);

		//check if too old
		if (!$featuredPostId or $featuredPostDate + $featuredPostRotationTime < time())
			return PropertyModel::featureNewPost();

		//check if post was deleted
		$featuredPost = PostModel::findById($featuredPostId, false);
		if (!$featuredPost)
			return PropertyModel::featureNewPost();

		return $featuredPost;
	}
}
