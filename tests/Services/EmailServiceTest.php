<?php
namespace Szurubooru\Tests\Services;

class EmailServiceTest extends \Szurubooru\Tests\AbstractTestCase
{
	public function testEmailWithoutAt()
	{
		$emailService = new \Szurubooru\Services\EmailService();

		$this->setExpectedException(\DomainException::class);
		$emailService->validateEmail('ghost');
	}

	public function testEmailWithoutDotInDomain()
	{
		$emailService = new \Szurubooru\Services\EmailService();

		$this->setExpectedException(\DomainException::class);
		$emailService->validateEmail('ghost@cemetery');
	}

	public function testValidEmail()
	{
		$emailService = new \Szurubooru\Services\EmailService();

		$this->assertNull($emailService->validateEmail('ghost@cemetery.consulting'));
	}
}
