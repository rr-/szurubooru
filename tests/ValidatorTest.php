<?php
namespace Szurubooru\Tests;

final class ValidatorTest extends \Szurubooru\Tests\AbstractTestCase
{
	private $configMock;

	public function setUp()
	{
		parent::setUp();
		$this->configMock = $this->mockConfig();
	}

	public function testMinLengthName()
	{
		$validator = $this->getValidator();
		$this->setExpectedException(\Exception::class, 'Object must have at least 50 character(s)');
		$validator->validateMinLength('too short', 50);
	}

	public function testMaxLengthName()
	{
		$validator = $this->getValidator();
		$this->setExpectedException(\Exception::class, 'Object must have at most 1 character(s)');
		$validator->validateMaxLength('too long', 1);
	}

	public function testValidLengthName()
	{
		$validator = $this->getValidator();
		$this->assertNull($validator->validateLength('fitting', 1, 50));
		$this->assertNull($validator->validateMaxLength('fitting', 50));
		$this->assertNull($validator->validateMinLength('fitting', 1));
	}

	public function testEmptyUserName()
	{
		$this->configMock->set('users/minUserNameLength', 0);
		$this->configMock->set('users/maxUserNameLength', 1);
		$this->setExpectedException(\Exception::class, 'User name cannot be empty');
		$userName = '';
		$validator = $this->getValidator();
		$validator->validateUserName($userName);
	}

	public function testTooShortUserName()
	{
		$this->configMock->set('users/minUserNameLength', 30);
		$this->configMock->set('users/maxUserNameLength', 50);
		$this->setExpectedException(\Exception::class, 'User name must have at least 30 character(s)');
		$userName = 'godzilla';
		$validator = $this->getValidator();
		$validator->validateUserName($userName);
	}

	public function testTooLongUserName()
	{
		$this->configMock->set('users/minUserNameLength', 30);
		$this->configMock->set('users/maxUserNameLength', 50);
		$this->setExpectedException(\Exception::class, 'User name must have at most 50 character(s)');
		$userName = 'godzilla' . str_repeat('a', 50);
		$validator = $this->getValidator();
		$validator->validateUserName($userName);
	}

	public function testUserNameWithInvalidCharacters()
	{
		$this->configMock->set('users/minUserNameLength', 0);
		$this->configMock->set('users/maxUserNameLength', 100);
		$userName = '..:xXx:godzilla:xXx:..';
		$this->setExpectedException(\Exception::class, 'User name may contain only');
		$validator = $this->getValidator();
		$validator->validateUserName($userName);
	}

	public function testEmailWithoutAt()
	{
		$validator = $this->getValidator();
		$this->setExpectedException(\DomainException::class);
		$validator->validateEmail('ghost');
	}

	public function testEmailWithoutDotInDomain()
	{
		$validator = $this->getValidator();
		$this->setExpectedException(\DomainException::class);
		$validator->validateEmail('ghost@cemetery');
	}

	public function testValidEmail()
	{
		$validator = $this->getValidator();
		$this->assertNull($validator->validateEmail('ghost@cemetery.consulting'));
	}

	public function testEmptyPassword()
	{
		$this->configMock->set('security/minPasswordLength', 0);
		$this->setExpectedException(\Exception::class, 'Password cannot be empty');
		$validator = $this->getValidator();
		$validator->validatePassword('');
	}

	public function testTooShortPassword()
	{
		$this->configMock->set('security/minPasswordLength', 10000);
		$this->setExpectedException(\Exception::class, 'Password must have at least 10000 character(s)');
		$validator = $this->getValidator();
		$validator->validatePassword('password123');
	}

	public function testNonAsciiPassword()
	{
		$this->configMock->set('security/minPasswordLength', 0);
		$this->setExpectedException(\Exception::class, 'Password may contain only');
		$validator = $this->getValidator();
		$validator->validatePassword('良いパスワード');
	}

	public function testValidPassword()
	{
		$this->configMock->set('security/minPasswordLength', 0);
		$validator = $this->getValidator();
		$this->assertNull($validator->validatePassword('password'));
	}

	public function testNoTags()
	{
		$this->setExpectedException(\Exception::class, 'Tags cannot be empty');
		$validator = $this->getValidator();
		$validator->validatePostTags([]);
	}

	public function testEmptyTags()
	{
		$this->setExpectedException(\Exception::class, 'Tags cannot be empty');
		$validator = $this->getValidator();
		$validator->validatePostTags(['good_tag', '']);
	}

	public function testTagsWithInvalidCharacters()
	{
		$this->setExpectedException(\Exception::class, 'Tags cannot contain any of following');
		$validator = $this->getValidator();
		$validator->validatePostTags(['good_tag', 'bad' . chr(160) . 'tag']);
	}

	public function testValidTags()
	{
		$validator = $this->getValidator();
		$this->assertNull($validator->validatePostTags(['good_tag', 'good_tag2', 'góód_as_well', ':3']));
	}

	private function getValidator()
	{
		return new \Szurubooru\Validator($this->configMock);
	}
}
