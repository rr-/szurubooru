<?php
namespace Szurubooru\Entities;

final class Token extends Entity
{
	const PURPOSE_LOGIN = 'login';
	const PURPOSE_ACTIVATE = 'activate';
	const PURPOSE_PASSWORD_RESET = 'passwordReset';

	public $name;
	public $purpose;
	public $additionalData;
}
