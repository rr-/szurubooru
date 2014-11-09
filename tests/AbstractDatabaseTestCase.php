<?php
namespace Szurubooru\Tests;
use Szurubooru\Config;
use Szurubooru\Dao\PublicFileDao;
use Szurubooru\DatabaseConnection;
use Szurubooru\Entities\Post;
use Szurubooru\Entities\User;
use Szurubooru\Injector;
use Szurubooru\Tests\AbstractTestCase;

abstract class AbstractDatabaseTestCase extends AbstractTestCase
{
	protected $databaseConnection;

	public function setUp()
	{
		parent::setUp();
		$realConfig = Injector::get(Config::class);
		$config = $this->mockConfig($this->createTestDirectory());
		$config->set('database/dsn', $realConfig->database->tests->dsn);
		$config->set('database/user', $realConfig->database->tests->user);
		$config->set('database/password', $realConfig->database->tests->password);

		$this->databaseConnection = new DatabaseConnection($config);
		$this->databaseConnection->getPDO()->exec('USE szuru_test');
		$this->databaseConnection->getPDO()->beginTransaction();
		Injector::set(DatabaseConnection::class, $this->databaseConnection);
	}

	public function tearDown()
	{
		$this->databaseConnection->getPDO()->rollBack();
		parent::tearDown();
		if ($this->databaseConnection)
			$this->databaseConnection->close();
	}

	protected static function getTestPost()
	{
		$post = new Post();
		$post->setName('test');
		$post->setUploadTime(date('c'));
		$post->setSafety(Post::POST_SAFETY_SAFE);
		$post->setContentType(Post::POST_TYPE_YOUTUBE);
		$post->setContentChecksum('whatever');
		return $post;
	}

	protected static function getTestUser($userName = 'test')
	{
		$user = new User();
		$user->setName($userName);
		$user->setPasswordHash('whatever');
		$user->setLastLoginTime(date('c', mktime(1, 2, 3)));
		$user->setRegistrationTime(date('c', mktime(3, 2, 1)));
		$user->setAccessRank(User::ACCESS_RANK_REGULAR_USER);
		return $user;
	}
}
