<?php
class StaticPagesController
{
	public function mainPageView()
	{
		$context = Core::getContext();
		$context->transport->postCount = PostModel::getCount();
		$context->viewName = 'static-main';
		$context->transport->postSpaceUsage = PostModel::getSpaceUsage();

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
		$config = Core::getConfig();
		$context = Core::getContext();

		if (empty($config->help->paths) or empty($config->help->title))
			throw new SimpleException('Help is disabled');

		$tab = $tab ?: array_keys($config->help->subTitles)[0];
		if (!isset($config->help->paths[$tab]))
			throw new SimpleException('Invalid tab');

		$context->viewName = 'static-help';
		$context->path = TextHelper::absolutePath($config->help->paths[$tab]);
		$context->tab = $tab;
	}

	public function fatalErrorView($code = null)
	{
		throw new SimpleException('Error ' . $code . ' while retrieving ' . $_SERVER['REQUEST_URI']);
	}
}
