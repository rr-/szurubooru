<?php
namespace Szurubooru\Entities;

final class Token extends Entity
{
	const PURPOSE_LOGIN = 0;
	const PURPOSE_ACTIVATE = 1;
	const PURPOSE_PASSWORD_RESET = 2;

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
		$this->purpose = $purpose;
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
