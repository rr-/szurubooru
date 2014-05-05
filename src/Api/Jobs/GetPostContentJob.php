<?php
class GetPostContentJob extends AbstractJob
{
	protected $post;

	public function prepare()
	{
		$this->post = PostModel::findByName($this->getArgument(self::POST_NAME));
	}

	public function execute()
	{
		$post = $this->post;
		$config = getConfig();

		$path = $config->main->filesPath . DS . $post->getName();
		$path = TextHelper::absolutePath($path);
		if (!file_exists($path))
			throw new SimpleNotFoundException('Post file does not exist');
		if (!is_readable($path))
			throw new SimpleException('Post file is not readable');

		$fileName = sprintf('%s_%s_%s.%s',
			$config->main->title,
			$post->getId(),
			join(',', array_map(function($tag) { return $tag->getName(); }, $post->getTags())),
			TextHelper::resolveMimeType($post->mimeType) ?: 'dat');
		$fileName = preg_replace('/[[:^print:]]/', '', $fileName);

		return new ApiFileOutput($path, $fileName);
	}

	public function requiresPrivilege()
	{
		$post = $this->post;
		$privileges = [];

		if ($post->hidden)
			$privileges []= new Privilege(Privilege::ViewPost, 'hidden');

		$privileges []= new Privilege(Privilege::ViewPost, $post->getSafety()->toString());

		return $privileges;
	}
}
