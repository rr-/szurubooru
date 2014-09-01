<?php
namespace Szurubooru\Services;

class EmailService
{
	//todo: refactor this to generic validation
	public function validateEmail($email)
	{
		if (!$email)
			return;

		if (!preg_match('/^[^@]+@[^@]+\.\w+$/', $email))
			throw new \DomainException('Specified e-mail appears to be invalid.');
	}
}
