<?php
class StaticPagesController
{
	public function mainPageView()
	{
		$context = getContext();
		$context->transport->postCount = PostModel::getCount();
		$context->viewName = 'static-main';

		PostModel::featureRandomPostIfNecessary();
		$featuredPost = PostModel::getFeaturedPost();
		if ($featuredPost)
		{
			$context->featuredPost = $featuredPost;
			$context->featuredPostUnixTime = PropertyModel::get(PropertyModel::FeaturedPostUnixTime);
			$context->featuredPostUser = UserModel::tryGetByName(
				PropertyModel::get(PropertyModel::FeaturedPostUserName));
		}
	}

	public function helpView($tab = null)
	{
		$config = getConfig();
		$context = getContext();

		if (empty($config->help->paths) or empty($config->help->title))
			throw new SimpleException('Help is disabled');

		$tab = $tab ?: array_keys($config->help->subTitles)[0];
		if (!isset($config->help->paths[$tab]))
			throw new SimpleException('Invalid tab');

		$context->viewName = 'static-help';
		$context->path = TextHelper::absolutePath($config->help->paths[$tab]);
		$context->tab = $tab;
	}
}
