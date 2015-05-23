<?php
namespace Szurubooru\Upgrades;
use Szurubooru\Dao\PostDao;
use Szurubooru\Dao\PublicFileDao;
use Szurubooru\DatabaseConnection;
use Szurubooru\Entities\Post;
use Szurubooru\Helpers\MimeHelper;

class Upgrade38 implements IUpgrade
{
	private $postDao;
	private $fileDao;

	public function __construct(
		PostDao $postDao,
		PublicFileDao $fileDao)
	{
		$this->postDao = $postDao;
		$this->fileDao = $fileDao;
	}

	public function run(DatabaseConnection $databaseConnection)
	{
		$posts = $this->postDao->findAll();
		$progress = 0;
		foreach ($posts as $post)
		{
			if ($post->getContentType() === Post::POST_TYPE_IMAGE)
			{
				$fullPath = $this->fileDao->getFullPath($post->getContentPath());
				try
				{
					$contents = file_get_contents($fullPath);
				}
				catch (\Exception $e)
				{
					continue;
				}
				if (MimeHelper::isBufferAnimatedGif($contents))
				{
					$post->setContentType(Post::POST_TYPE_ANIMATED_IMAGE);
					$this->postDao->save($post);
				}
				if (++ $progress == 100)
				{
					echo '.';
					$progress = 0;
				}
			}
		}
	}
}
