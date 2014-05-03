<?php
class GetPostContentJob extends AbstractJob
{
	public function execute()
	{
		$post = PostModel::findByName($this->getArgument(self::POST_NAME));

		//todo: refactor this so that requiresPrivilege can accept multiple privileges
		if ($post->hidden)
			Access::assert(Privilege::RetrievePost, 'hidden');
		Access::assert(Privilege::RetrievePost);
		Access::assert(Privilege::RetrievePost, PostSafety::toString($post->safety));

		$config = getConfig();

		$path = $config->main->filesPath . DS . $post->name;
		$path = TextHelper::absolutePath($path);
		if (!file_exists($path))
			throw new SimpleNotFoundException('Post file does not exist');
		if (!is_readable($path))
			throw new SimpleException('Post file is not readable');

		$fileName = sprintf('%s_%s_%s.%s',
			$config->main->title,
			$post->id,
			join(',', array_map(function($tag) { return $tag->name; }, $post->getTags())),
			TextHelper::resolveMimeType($post->mimeType) ?: 'dat');
		$fileName = preg_replace('/[[:^print:]]/', '', $fileName);

		return new ApiFileOutput($path, $fileName);
	}

	public function requiresPrivilege()
	{
		//temporarily enforced in execute
		return false;
	}

	public function requiresAuthentication()
	{
		return false;
	}

	public function requiresConfirmedEmail()
	{
		return false;
	}
}
