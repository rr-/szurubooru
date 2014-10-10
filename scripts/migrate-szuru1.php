<?php
require_once __DIR__
	. DIRECTORY_SEPARATOR . '..'
	. DIRECTORY_SEPARATOR . 'src'
	. DIRECTORY_SEPARATOR . 'Bootstrap.php';

use \Szurubooru\Dao\CommentDao;
use \Szurubooru\Dao\PostDao;
use \Szurubooru\Dao\PublicFileDao;
use \Szurubooru\Dao\TagDao;
use \Szurubooru\Dao\TransactionManager;
use \Szurubooru\Dao\UserDao;
use \Szurubooru\DatabaseConnection;
use \Szurubooru\Entities\Comment;
use \Szurubooru\Entities\Post;
use \Szurubooru\Entities\Tag;
use \Szurubooru\Entities\User;
use \Szurubooru\Injector;
use \Szurubooru\Services\HistoryService;
use \Szurubooru\Services\NetworkingService;
use \Szurubooru\Services\TagService;

if (!isset($argv[1]) or !isset($argv[2]))
{
	echo 'Usage: ' . __FILE__ . ' DSN DATADIR';
	exit(1);
}

$sourceDatabaseDsn = $argv[1];
$sourcePdo = new \PDO($sourceDatabaseDsn);
$sourcePdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
$sourcePdo->setAttribute(\PDO::ATTR_DEFAULT_FETCH_MODE, \PDO::FETCH_ASSOC);
$sourcePublicHtmlDirectory = $argv[2];

function removeRecursively($dir)
{
	if (!file_exists($dir))
		return;

	if (!is_dir($dir))
		throw new \Exception('Not a dir: ' . $dir);

	$files = new \RecursiveIteratorIterator(
		new \RecursiveDirectoryIterator($dir, \RecursiveDirectoryIterator::SKIP_DOTS),
		\RecursiveIteratorIterator::CHILD_FIRST);

	foreach ($files as $fileinfo)
	{
		$todo = ($fileinfo->isDir() ? 'rmdir' : 'unlink');
		$todo($fileinfo->getRealPath());
	}

	rmdir($dir);
}

abstract class Task
{
	public function execute()
	{
		echo $this->getDescription() . '...';
		$this->run();
		echo PHP_EOL;
	}

	protected function progress()
	{
		echo '.';
	}

	protected function withProgress($source, $callback, $chunkSize = 666, callable $callbackLoopRunner = null)
	{
		if ($source instanceof \Traversable)
			$source = iterator_to_array($source);

		if ($callbackLoopRunner === null)
		{
			$callbackLoopRunner = function($chunk) use ($callback)
			{
				$this->progress();
				foreach ($chunk as $arg)
				{
					$callback($arg);
				}
			};
		}

		foreach (array_chunk($source, $chunkSize) as $chunk)
		{
			$callbackLoopRunner($chunk);
		}
	}

	abstract protected function getDescription();

	abstract protected function run();
}

abstract class PdoTask extends Task
{
	protected function commitInChunks($source, $callback, $chunkSize = 666)
	{
		$transactionManager = Injector::get(TransactionManager::class);
		$this->withProgress($source, $callback, $chunkSize, function($chunk) use ($transactionManager, $callback)
		{
			$transactionManager->commit(function() use ($callback, $chunk)
			{
				$this->progress();
				foreach ($chunk as $arg)
				{
					$callback($arg);
				}
			});
		});
	}
}

abstract class SourcePdoTask extends PdoTask
{
	protected $sourcePdo;

	public function __construct($sourcePdo)
	{
		$this->sourcePdo = $sourcePdo;
	}
}

class RemoveAllTablesTask extends Task
{
	protected function getDescription()
	{
		return 'truncating tables in target database';
	}

	protected function run()
	{
		$targetPdo = Injector::get(DatabaseConnection::class)->getPDO();
		$targetPdo->exec('DELETE FROM globals');
		$targetPdo->exec('DELETE FROM postRelations');
		$targetPdo->exec('DELETE FROM postTags');
		$targetPdo->exec('DELETE FROM scores');
		$targetPdo->exec('DELETE FROM favorites');
		$targetPdo->exec('DELETE FROM snapshots');
		$targetPdo->exec('DELETE FROM comments');
		$targetPdo->exec('DELETE FROM posts');
		$targetPdo->exec('DELETE FROM users');
		$targetPdo->exec('DELETE FROM tokens');
		$targetPdo->exec('DELETE FROM tags');
	}
}

class RemoveAllThumbnailsTask extends Task
{
	protected function getDescription()
	{
		return 'removing thumbnail content in target dir';
	}

	protected function run()
	{
		$publicFileDao = Injector::get(PublicFileDao::class);
		$dir = $publicFileDao->getFullPath('thumbnails');

		foreach (scandir($dir) as $fn)
		{
			$path = $dir . DIRECTORY_SEPARATOR . $fn;
			if ($fn{0} === '.' or !is_dir($path))
				continue;
			removeRecursively($path);
		}
	}
}

class RemoveAllPostContentTask extends Task
{
	protected function getDescription()
	{
		return 'removing post content in target dir';
	}

	protected function run()
	{
		$publicFileDao = Injector::get(PublicFileDao::class);
		$dir = $publicFileDao->getFullPath('posts');
		removeRecursively($dir);
	}
}

class CopyPostContentTask extends Task
{
	private $sourceDir;

	public function __construct($publicHtmlDir)
	{
		$this->sourceDir = $publicHtmlDir . DIRECTORY_SEPARATOR . 'files';
	}

	protected function getDescription()
	{
		return 'copying post content';
	}

	protected function run()
	{
		$publicFileDao = Injector::get(PublicFileDao::class);
		$targetDir = $publicFileDao->getFullPath('posts');
		if (!file_exists($targetDir))
			mkdir($targetDir, 0777, true);

		$this->withProgress(
			glob($this->sourceDir . DIRECTORY_SEPARATOR . '*'),
			function ($sourcePath) use ($targetDir)
			{
				$targetPath = $targetDir . DIRECTORY_SEPARATOR . basename($sourcePath);
				copy($sourcePath, $targetPath);
			},
			100);
	}
}

class CopyPostThumbSourceTask extends Task
{
	private $sourceDir;

	public function __construct($publicHtmlDir)
	{
		$this->sourceDir = $publicHtmlDir . DIRECTORY_SEPARATOR . 'thumbs';
	}

	protected function getDescription()
	{
		return 'copying post thumbnail sources';
	}

	protected function run()
	{
		$publicFileDao = Injector::get(PublicFileDao::class);
		$targetDir = $publicFileDao->getFullPath('posts');
		if (!file_exists($targetDir))
			mkdir($targetDir, 0777, true);

		$this->withProgress(
			glob($this->sourceDir . DIRECTORY_SEPARATOR . '*.thumb_source'),
			function($sourcePath) use ($targetDir)
			{
				$targetPath = $targetDir . DIRECTORY_SEPARATOR . str_replace('.thumb_source', '-custom-thumb', basename($sourcePath));
				copy($sourcePath, $targetPath);
			},
			100);
	}
}

class CopyUsersTask extends SourcePdoTask
{
	protected function getDescription()
	{
		return 'copying users';
	}

	protected function run()
	{
		$userDao = Injector::get(UserDao::class);
		$this->commitInChunks($this->sourcePdo->query('SELECT * FROM user'), function($arr) use ($userDao)
		{
			$user = new User;
			$user->setId($arr['id']);
			$user->setName($arr['name']);
			$user->setPasswordSalt($arr['pass_salt']);
			$user->setPasswordHash($arr['pass_hash']);
			$user->setEmailUnconfirmed($arr['email_unconfirmed']);
			$user->setEmail($arr['email_confirmed']);
			$user->setRegistrationTime(date('c', $arr['join_date']));
			$user->setBanned(false);
			$user->setAccountConfirmed(true);
			$user->setLastLoginTime(date('c', $arr['last_login_date']));

			switch ($arr['avatar_style'])
			{
				case '1':
					$user->setAvatarStyle(User::AVATAR_STYLE_GRAVATAR);
					break;
				case '2':
					$user->setAvatarStyle(User::AVATAR_STYLE_MANUAL);
					break;
				case '3':
					$user->setAvatarStyle(User::AVATAR_STYLE_BLANK);
					break;
			}

			switch ($arr['access_rank'])
			{
				case '0':
					$user->setAccessRank(User::ACCESS_RANK_ANONYMOUS);
					break;
				case '1':
					$user->setAccessRank(User::ACCESS_RANK_REGULAR_USER);
					break;
				case '2':
					$user->setAccessRank(User::ACCESS_RANK_POWER_USER);
					break;
				case '3':
					$user->setAccessRank(User::ACCESS_RANK_MODERATOR);
					break;
				case '4':
					$user->setAccessRank(User::ACCESS_RANK_ADMINISTRATOR);
					break;
			}
			$userDao->create($user);
		});
	}
}

class CopyPostsTask extends SourcePdoTask
{
	protected function getDescription()
	{
		return 'copying posts';
	}

	protected function run()
	{
		$postDao = Injector::get(PostDao::class);
		$this->commitInChunks($this->sourcePdo->query('SELECT * FROM post'), function($arr) use ($postDao)
		{
			$post = new Post();
			$post->setImageWidth($arr['image_width']);
			$post->setImageHeight($arr['image_height']);
			$post->setUserId($arr['uploader_id']);
			$post->setSource($arr['source']);
			$post->setContentMimeType($arr['mime_type']);
			$post->setContentChecksum($arr['file_hash']);
			$post->setOriginalFileSize($arr['file_size']);
			$post->setOriginalFileName($arr['orig_name']);
			$post->setName($arr['name']);
			$post->setId($arr['id']);
			$post->setUploadTime(date('c', $arr['upload_date']));
			$post->setLastEditTime(date('c', $arr['upload_date']));

			switch ($arr['safety'])
			{
				case 1:
					$post->setSafety(Post::POST_SAFETY_SAFE);
					break;
				case 2:
					$post->setSafety(Post::POST_SAFETY_SKETCHY);
					break;
				case 3:
					$post->setSafety(Post::POST_SAFETY_UNSAFE);
					break;
			}

			switch ($arr['type'])
			{
				case 1:
					$post->setContentType(Post::POST_TYPE_IMAGE);
					break;
				case 2:
					$post->setContentType(Post::POST_TYPE_FLASH);
					break;
				case 3:
					$post->setContentType(Post::POST_TYPE_YOUTUBE);
					break;
				case 4:
					$post->setContentType(Post::POST_TYPE_VIDEO);
					break;
			}
			$postDao->create($post);
		});
	}
}

class CopyCommentsTask extends SourcePdoTask
{
	protected function getDescription()
	{
		return 'copying comments';
	}

	protected function run()
	{
		$commentDao = Injector::get(CommentDao::class);
		$this->commitInChunks($this->sourcePdo->query('SELECT * FROM comment'), function($arr) use ($commentDao)
		{
			$comment = new Comment();
			$comment->setPostId($arr['post_id']);
			$comment->setUserId($arr['commenter_id']);
			$comment->setCreationTime(date('c', $arr['comment_date']));
			$comment->setLastEditTime(date('c', $arr['comment_date']));
			$comment->setText($arr['text']);
			$commentDao->save($comment);
		});
	}
}

class CopyTagsTask extends SourcePdoTask
{
	protected function getDescription()
	{
		return 'copying tags';
	}

	protected function run()
	{
		$tagDao = Injector::get(TagDao::class);
		$this->commitInChunks($this->sourcePdo->query('SELECT * FROM tag'), function($arr) use ($tagDao)
		{
			$tag = new Tag();
			$tag->setId($arr['id']);
			$tag->setName($arr['name']);
			$tag->setCreationTime($arr['creation_date'] ? date('c', $arr['creation_date']) : date('c'));
			$tagDao->create($tag);
		});
	}
}

class CopyPostRelationsTask extends SourcePdoTask
{
	protected function getDescription()
	{
		return 'copying relations';
	}

	protected function run()
	{
		$targetPdo = Injector::get(DatabaseConnection::class)->getPDO();
		$this->commitInChunks($this->sourcePdo->query('SELECT * FROM crossref'), function($arr) use ($targetPdo)
		{
			$targetPdo->exec(
				sprintf('INSERT INTO postRelations (post1id, post2id) VALUES (%d, %d)',
				intval($arr['post_id']),
				intval($arr['post2_id'])));
		});
	}
}

class CopyPostFavoritesTask extends SourcePdoTask
{
	protected function getDescription()
	{
		return 'copying favorites';
	}

	protected function run()
	{
		$targetPdo = Injector::get(DatabaseConnection::class)->getPDO();
		$this->commitInChunks($this->sourcePdo->query('SELECT * FROM favoritee'), function($arr) use ($targetPdo)
		{
			$targetPdo->exec(
				sprintf('INSERT INTO favorites (userId, postId) VALUES (%d, %d)',
				intval($arr['user_id']),
				intval($arr['post_id'])));
		});
	}
}

class CopyPostScoresTask extends SourcePdoTask
{
	protected function getDescription()
	{
		return 'copying post scores';
	}

	protected function run()
	{
		$targetPdo = Injector::get(DatabaseConnection::class)->getPDO();
		$this->commitInChunks($this->sourcePdo->query('SELECT * FROM post_score'), function($arr) use ($targetPdo)
		{
			$targetPdo->exec(
				sprintf('INSERT INTO scores (userId, postId, score) VALUES (%d, %d, %d)',
				intval($arr['user_id']),
				intval($arr['post_id']),
				intval($arr['score'])));
		});
	}
}

class CopyPostTagRelationsTask extends SourcePdoTask
{
	protected function getDescription()
	{
		return 'copying post-tag relations';
	}

	protected function run()
	{
		$targetPdo = Injector::get(DatabaseConnection::class)->getPDO();
		$this->commitInChunks($this->sourcePdo->query('SELECT * FROM post_tag'), function($arr) use ($targetPdo)
		{
			$targetPdo->exec(
				sprintf('INSERT INTO postTags (postId, tagId) VALUES (%d, %d)',
				intval($arr['post_id']),
				intval($arr['tag_id'])));
		});
	}
}

class PreparePostHistoryTask extends PdoTask
{
	protected function getDescription()
	{
		return 'preparing initial post history';
	}

	protected function run()
	{
		$postDao = Injector::get(PostDao::class);
		$historyService = Injector::get(HistoryService::class);
		$this->commitInChunks($postDao->findAll(), function($post) use ($postDao, $historyService)
		{
			$historyService->saveSnapshot($historyService->getPostChangeSnapshot($post));
		});
	}
}

class ExportTagsTask extends Task
{
	protected function getDescription()
	{
		return 'exporting tags';
	}

	protected function run()
	{
		$tagService = Injector::get(TagService::class);
		$tagService->exportJson();
	}
}

class DownloadYoutubeThumbnailsTask extends PdoTask
{
	protected function getDescription()
	{
		return 'downloading youtube thumbnails';
	}

	protected function run()
	{
		$postDao = Injector::get(PostDao::class);
		$networkingService = Injector::get(NetworkingService::class);
		$this->commitInChunks($postDao->findAll(), function($post) use ($postDao, $networkingService)
		{
			if ($post->getContentType() !== Post::POST_TYPE_YOUTUBE)
				return;

			$youtubeId = $post->getContentChecksum();
			$youtubeThumbnailUrl = 'http://img.youtube.com/vi/' . $youtubeId . '/mqdefault.jpg';
			try
			{
				$youtubeThumbnail = $networkingService->download($youtubeThumbnailUrl);
			}
			catch (\Exception $e)
			{
				return;
			}
			$post->setThumbnailSourceContent($youtubeThumbnail);
			$postDao->save($post);
		});
	}
}

$tasks =
[
	new RemoveAllTablesTask(),
	new RemoveAllThumbnailsTask(),
	new RemoveAllPostContentTask(),
	new CopyPostContentTask($sourcePublicHtmlDirectory),
	new CopyPostThumbSourceTask($sourcePublicHtmlDirectory),
	new CopyUsersTask($sourcePdo),
	new CopyPostsTask($sourcePdo),
	new CopyCommentsTask($sourcePdo),
	new CopyTagsTask($sourcePdo),
	new CopyPostRelationsTask($sourcePdo),
	new CopyPostFavoritesTask($sourcePdo),
	new CopyPostScoresTask($sourcePdo),
	new CopyPostTagRelationsTask($sourcePdo),
	new PreparePostHistoryTask(),
	new ExportTagsTask(),
	new DownloadYoutubeThumbnailsTask(),
];

foreach ($tasks as $task)
{
	$task->execute();
}
