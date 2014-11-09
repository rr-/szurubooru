<?php
namespace Szurubooru\Entities;

final class Tag extends Entity
{
	private $name;
	private $creationTime;
	private $banned = false;
	private $category = 'default';

	const LAZY_LOADER_IMPLIED_TAGS = 'implications';
	const LAZY_LOADER_SUGGESTED_TAGS = 'suggestions';

	const META_USAGES = 'usages';

	public function getName()
	{
		return $this->name;
	}

	public function setName($name)
	{
		$this->name = $name;
	}

	public function getCreationTime()
	{
		return $this->creationTime;
	}

	public function setCreationTime($creationTime)
	{
		$this->creationTime = $creationTime;
	}

	public function isBanned()
	{
		return $this->banned;
	}

	public function setBanned($banned)
	{
		$this->banned = boolval($banned);
	}

	public function getCategory()
	{
		return $this->category;
	}

	public function setCategory($category)
	{
		$this->category = $category;
	}

	public function getUsages()
	{
		return $this->getMeta(self::META_USAGES);
	}

	public function getImpliedTags()
	{
		return $this->lazyLoad(self::LAZY_LOADER_IMPLIED_TAGS, []);
	}

	public function setImpliedTags(array $impliedTags)
	{
		$this->lazySave(self::LAZY_LOADER_IMPLIED_TAGS, $impliedTags);
	}

	public function getSuggestedTags()
	{
		return $this->lazyLoad(self::LAZY_LOADER_SUGGESTED_TAGS, []);
	}

	public function setSuggestedTags(array $suggestedTags)
	{
		$this->lazySave(self::LAZY_LOADER_SUGGESTED_TAGS, $suggestedTags);
	}
}
