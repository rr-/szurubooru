<?php
namespace Szurubooru\Entities;

final class Token extends Entity
{
	const PURPOSE_LOGIN = 'login';

	public $name;
	public $purpose;
	public $additionalData;
}
