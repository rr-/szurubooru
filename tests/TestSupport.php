<?php
class TestSupport
{
	private $assert;

	public function __construct(Assert $assert)
	{
		$this->assert = $assert;
	}

	public function getPath($assetName)
	{
		return getConfig()->rootDir . DS . 'tests' . DS . 'TestFiles' . DS . $assetName;
	}

	public function assertTagNames($post, $tags)
	{
		$tagNames = $this->getTagNames($tags);
		$postTagNames = $this->getTagNames($post->getTags());
		$this->assert->areEquivalent($tagNames, $postTagNames);
	}

	public function getTagNames(array $tags)
	{
		$tagNames = array_map(
			function($tag)
			{
				return $tag->getName();
			}, $tags);
		natcasesort($tagNames);
		$tagNames = array_values($tagNames);
		return $tagNames;
	}
}
