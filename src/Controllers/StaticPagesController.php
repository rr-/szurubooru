<?php
class StaticPagesController extends AbstractController
{
	public function mainPageView()
	{
		$context = Core::getContext();
		$context->transport->postCount = PostModel::getCount();
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

		$this->renderView('static-main');
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

		$context->path = TextHelper::absolutePath($config->help->paths[$tab]);
		$context->tab = $tab;

		$this->renderView('static-help');
	}

	public function fatalErrorView($code = null)
	{
		throw new SimpleException('Error ' . $code . ' while retrieving ' . $_SERVER['REQUEST_URI']);
	}
}
