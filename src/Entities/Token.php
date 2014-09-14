<?php
namespace Szurubooru\Entities;

final class Token extends Entity
{
	const PURPOSE_LOGIN = 1;
	const PURPOSE_ACTIVATE = 2;
	const PURPOSE_PASSWORD_RESET = 3;

	protected $name;
	protected $purpose;
	protected $additionalData;

	public function getName()
	{
		return $this->name;
	}

	public function setName($name)
	{
		$this->name = $name;
	}

	public function getPurpose()
	{
		return $this->purpose;
	}

	public function setPurpose($purpose)
	{
		$this->purpose = intval($purpose);
	}

	public function getAdditionalData()
	{
		return $this->additionalData;
	}

	public function setAdditionalData($additionalData)
	{
		$this->additionalData = $additionalData;
	}
}
