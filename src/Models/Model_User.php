<?php
class Model_User extends RedBean_SimpleModel
{
	public function getAvatarUrl($size = 32)
	{
		$subject = !empty($this->email)
			? $this->email
			: $this->name;
		$hash = md5(strtolower(trim($subject)));
		$url = 'http://www.gravatar.com/avatar/' . $hash . '?s=' . $size . '&d=retro';
		return $url;
	}

	public function getSetting($key)
	{
		$settings = json_decode($this->settings, true);
		return isset($settings[$key])
			? $settings[$key]
			: null;
	}

	public function setSetting($key, $value)
	{
		$settings = json_decode($this->settings, true);
		$settings[$key] = $value;
		$settings = json_encode($settings);
		if (strlen($settings) > 200)
			throw new SimpleException('Too much data');
		$this->settings = $settings;
	}

	public function hasEnabledSafety($safety)
	{
		return $this->getSetting('safety-' . $safety) !== false;
	}

	public function enableSafety($safety, $enabled)
	{
		if (!$enabled)
		{
			$this->setSetting('safety-' . $safety, false);
			$anythingEnabled = false;
			foreach (PostSafety::getAll() as $safety)
				if (self::hasEnabledSafety($safety))
					$anythingEnabled = true;
			if (!$anythingEnabled)
				$this->setSetting('safety-' . PostSafety::Safe, true);
		}
		else
		{
			$this->setSetting('safety-' . $safety, true);
		}
	}
}
