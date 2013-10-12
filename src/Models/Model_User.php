<?php
class Model_User extends RedBean_SimpleModel
{
	public function avatarUrl($size = 32)
	{
		$subject = !empty($this->email)
			? $this->email
			: $this->name;
		$hash = md5(strtolower(trim($subject)));
		$url = 'http://www.gravatar.com/avatar/' . $hash . '?s=' . $size . '&d=retro';
		return $url;
	}
}
