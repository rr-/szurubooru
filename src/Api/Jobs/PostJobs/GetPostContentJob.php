<?php
class GetPostContentJob extends AbstractJob
{
	protected $postRetriever;

	public function __construct()
	{
		$this->postRetriever = new SafePostRetriever($this);
	}

	public function execute()
	{
		$post = $this->postRetriever->retrieve();
		$config = Core::getConfig();

		$path = $post->tryGetWorkingFullPath();
		if (!$path)
			throw new SimpleNotFoundException('Post file does not exist');

		$fileName = sprintf('%s_%s_%s.%s',
			$config->main->title,
			$post->getId(),
			join(',', array_map(function($tag) { return $tag->getName(); }, $post->getTags())),
			TextHelper::resolveMimeType($post->getMimeType()) ?: 'dat');
		$fileName = preg_replace('/[[:^print:]]/', '', $fileName);

		return new ApiFileOutput($path, $fileName);
	}

	public function getRequiredArguments()
	{
		return $this->postRetriever->getRequiredArguments();
	}

	public function getRequiredPrivileges()
	{
		$post = $this->postRetriever->retrieve();
		$privileges = [];

		if ($post->isHidden())
			$privileges []= new Privilege(Privilege::ViewPost, 'hidden');

		$privileges []= new Privilege(Privilege::ViewPost, $post->getSafety()->toString());

		return $privileges;
	}
}
