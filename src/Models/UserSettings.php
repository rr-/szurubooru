<?php
class UserSettings implements IValidatable
{
	const SETTING_SAFETY = 1;
	const SETTING_ENDLESS_SCROLLING = 2;
	const SETTING_POST_TAG_TITLES = 3;
	const SETTING_HIDE_DISLIKED_POSTS = 4;

	private $data;

	public function __construct($serializedString = null)
	{
		$this->data = [];
		if ($serializedString !== null)
			$this->fillFromSerializedString($serializedString);
		$this->attachDefaultSettings();
	}

	public function validate()
	{
		$serialized = $this->getAllAsSerializedString();
		if (strlen($serialized) > 200)
			throw new SimpleException('Too much data');

		$this->ensureCorrectTypes();
	}

	public function get($key)
	{
		return isset($this->data[$key])
			? $this->data[$key]
			: null;
	}

	public function set($key, $value)
	{
		$this->data[$key] = $value;
	}

	public function getAllAsSerializedString()
	{
		return json_encode($this->data);
	}

	public function getAllAsArray()
	{
		return $this->data;
	}


	public function hasEnabledSafety(PostSafety $safety)
	{
		$all = $this->get(self::SETTING_SAFETY);
		return ($all & $safety->toFlag()) == $safety->toFlag();
	}

	public function enableSafety(PostSafety $safety, $enabled)
	{
		$new = $this->get(self::SETTING_SAFETY);

		if (!$enabled)
			$new &= ~$safety->toFlag();
		else
			$new |= $safety->toFlag();

		$this->set(self::SETTING_SAFETY, $new);
		$this->attachDefaultSettings();
	}

	public function hasEnabledHidingDislikedPosts()
	{
		return $this->get(self::SETTING_HIDE_DISLIKED_POSTS);
	}

	public function enableHidingDislikedPosts($enabled)
	{
		$this->set(self::SETTING_HIDE_DISLIKED_POSTS, $enabled);
		$this->attachDefaultSettings();
	}

	public function hasEnabledPostTagTitles()
	{
		return $this->get(self::SETTING_POST_TAG_TITLES);
	}

	public function enablePostTagTitles($enabled)
	{
		$this->set(self::SETTING_POST_TAG_TITLES, $enabled);
		$this->attachDefaultSettings();
	}

	public function hasEnabledEndlessScrolling()
	{
		return $this->get(self::SETTING_ENDLESS_SCROLLING);
	}

	public function enableEndlessScrolling($enabled)
	{
		$this->set(self::SETTING_ENDLESS_SCROLLING, $enabled);
		$this->attachDefaultSettings();
	}

	private function fillFromSerializedString($string)
	{
		$this->data = json_decode($string, true);
	}

	private function attachDefaultSettings()
	{
		if ($this->get(self::SETTING_SAFETY) === null or $this->get(self::SETTING_SAFETY) === 0)
			$this->set(self::SETTING_SAFETY, (new PostSafety(PostSafety::Safe))->toInteger());

		if ($this->get(self::SETTING_HIDE_DISLIKED_POSTS) === null)
			$this->set(self::SETTING_HIDE_DISLIKED_POSTS, !(bool) Core::getConfig()->browsing->showDislikedPostsDefault);

		if ($this->get(self::SETTING_POST_TAG_TITLES) === null)
			$this->set(self::SETTING_POST_TAG_TITLES, (bool) Core::getConfig()->browsing->showPostTagTitlesDefault);

		if ($this->get(self::SETTING_ENDLESS_SCROLLING) === null)
			$this->set(self::SETTING_ENDLESS_SCROLLING, (bool) Core::getConfig()->browsing->endlessScrollingDefault);
	}

	private function ensureCorrectTypes()
	{
		$makeInt = ['TextHelper', 'toIntegerOrNull'];
		$makeBool = ['TextHelper', 'toBooleanOrNull'];

		$types =
			[
				[self::SETTING_SAFETY, $makeInt],
				[self::SETTING_HIDE_DISLIKED_POSTS, $makeBool],
				[self::SETTING_POST_TAG_TITLES, $makeBool],
				[self::SETTING_ENDLESS_SCROLLING, $makeBool],
			];

		foreach ($types as $item)
		{
			list ($setting, $func) = $item;
			$this->set($setting, $func($this->get($setting)));
		}

		$this->attachDefaultSettings();
	}
}
