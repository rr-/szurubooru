<?php
namespace Szurubooru\Tests\Services;
use Szurubooru\Dao\FileDao;
use Szurubooru\Tests\AbstractTestCase;

final class FileDaoTest extends AbstractTestCase
{
	public function testSaving()
	{
		$testDirectory = $this->createTestDirectory();
		$fileDao = new FileDao($testDirectory);
		$fileDao->save('dog.txt', 'awesome dog');
		$expected = 'awesome dog';
		$actual = file_get_contents($testDirectory . DIRECTORY_SEPARATOR . 'dog.txt');
		$this->assertEquals($expected, $actual);
	}

	public function testSavingSubfolders()
	{
		$testDirectory = $this->createTestDirectory();
		$fileDao = new FileDao($testDirectory);
		$fileDao->save('friends/dog.txt', 'hot dog');
		$expected = 'hot dog';
		$actual = file_get_contents($testDirectory . DIRECTORY_SEPARATOR . 'friends/dog.txt');
		$this->assertEquals($expected, $actual);
	}

	public function testLoading()
	{
		$testDirectory = $this->createTestDirectory();
		$fileDao = new FileDao($testDirectory);
		$fileDao->save('dog.txt', 'awesome dog');
		$this->assertEquals('awesome dog', $fileDao->load('dog.txt'));
	}

	public function testExists()
	{
		$testDirectory = $this->createTestDirectory();
		$fileDao = new FileDao($testDirectory);
		$fileDao->save('dog.txt', 'awesome dog');
		$this->assertTrue($fileDao->exists('dog.txt'));
		$this->assertFalse($fileDao->exists('fish.txt'));
	}

	public function testLoadingUnexisting()
	{
		$testDirectory = $this->createTestDirectory();
		$fileDao = new FileDao($testDirectory);
		$this->assertNull($fileDao->load('dog.txt'));
	}

	public function testDeleting()
	{
		$testDirectory = $this->createTestDirectory();
		$fileDao = new FileDao($testDirectory);
		$fileDao->save('dog.txt', 'awesome dog');
		$this->assertTrue(file_exists($testDirectory . DIRECTORY_SEPARATOR . 'dog.txt'));
		$fileDao->delete('dog.txt');
		$this->assertFalse(file_exists($testDirectory . DIRECTORY_SEPARATOR . 'dog.txt'));
	}
}
