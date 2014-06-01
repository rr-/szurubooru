<?php
class ScriptTest extends AbstractTest
{
	private $scriptsPath;

	public function __construct()
	{
		parent::__construct();
		$this->scriptsPath = Core::getConfig()->rootDir . DS . 'scripts' . DS;
	}

	public function testFindPosts()
	{
		$posts = $this->postMocker->mockMultiple(3);
		$output = $this->execute($this->scriptsPath . 'find-posts.php', []);
		$this->assert->isTrue(strpos($output, $posts[0]->getName()) !== false);
		$this->assert->isTrue(strpos($output, $posts[1]->getName()) !== false);
		$this->assert->isTrue(strpos($output, $posts[2]->getName()) !== false);
	}

	public function testFindPostsFilter()
	{
		$posts = $this->postMocker->mockMultiple(3);
		$output = $this->execute($this->scriptsPath . 'find-posts.php', ['idmin:' . $posts[1]->getId()]);
		$this->assert->isTrue(strpos($output, $posts[0]->getName()) === false);
		$this->assert->isTrue(strpos($output, $posts[1]->getName()) !== false);
		$this->assert->isTrue(strpos($output, $posts[2]->getName()) !== false);
	}

	public function testGenerateThumbs()
	{
		$posts = $this->postMocker->mockMultiple(3);
		$this->assert->isFalse(file_exists($posts[0]->getThumbnailPath()));
		$this->assert->isFalse(file_exists($posts[1]->getThumbnailPath()));
		$this->assert->isFalse(file_exists($posts[2]->getThumbnailPath()));
		$output = $this->execute($this->scriptsPath . 'generate-thumbs.php', []);
		$this->assert->isTrue(strpos($output, TextHelper::reprPost($posts[0])) !== false);
		$this->assert->isTrue(strpos($output, TextHelper::reprPost($posts[1])) !== false);
		$this->assert->isTrue(strpos($output, TextHelper::reprPost($posts[2])) !== false);
		$this->assert->isTrue(strpos($output, 'Don\'t forget to check access rights') !== false);
		$this->assert->isTrue(file_exists($posts[0]->getThumbnailPath()));
		$this->assert->isTrue(file_exists($posts[1]->getThumbnailPath()));
		$this->assert->isTrue(file_exists($posts[2]->getThumbnailPath()));
	}

	public function testDetachedFilesPrint()
	{
		$post = $this->postMocker->mockSingle();
		touch(Core::getConfig()->main->filesPath . DS . 'rubbish1');
		touch(Core::getConfig()->main->filesPath . DS . 'rubbish2');
		$output = $this->execute($this->scriptsPath . 'process-detached-files.php', ['-p']);
		$this->assert->isTrue(strpos($output, 'rubbish1') !== false);
		$this->assert->isTrue(strpos($output, 'rubbish2') !== false);
		$this->assert->isFalse(strpos($output, $post->getName()));
		$this->assert->isTrue(file_exists(Core::getConfig()->main->filesPath . DS . 'rubbish1'));
		$this->assert->isTrue(file_exists(Core::getConfig()->main->filesPath . DS . 'rubbish2'));
	}

	public function testDetachedFilesRemove()
	{
		$post = $this->postMocker->mockSingle();
		touch(Core::getConfig()->main->filesPath . DS . 'rubbish1');
		touch(Core::getConfig()->main->filesPath . DS . 'rubbish2');
		$output = $this->execute($this->scriptsPath . 'process-detached-files.php', ['-d']);
		$this->assert->isTrue(strpos($output, 'rubbish1') !== false);
		$this->assert->isTrue(strpos($output, 'rubbish2') !== false);
		$this->assert->isFalse(strpos($output, $post->getName()));
		$this->assert->isFalse(file_exists(Core::getConfig()->main->filesPath . DS . 'rubbish1'));
		$this->assert->isFalse(file_exists(Core::getConfig()->main->filesPath . DS . 'rubbish2'));
	}

	public function testDetachedFilesMove()
	{
		$post = $this->postMocker->mockSingle();
		touch(Core::getConfig()->main->filesPath . DS . 'rubbish1');
		touch(Core::getConfig()->main->filesPath . DS . 'rubbish2');
		$target = sys_get_temp_dir();
		$output = $this->execute($this->scriptsPath . 'process-detached-files.php', ['-m', $target]);
		$this->assert->isTrue(strpos($output, 'rubbish1') !== false);
		$this->assert->isTrue(strpos($output, 'rubbish2') !== false);
		$this->assert->isFalse(strpos($output, $post->getName()));
		$this->assert->isTrue(file_exists($target . DS . 'rubbish1'));
		$this->assert->isTrue(file_exists($target . DS . 'rubbish2'));
		unlink($target . DS . 'rubbish1');
		unlink($target . DS . 'rubbish2');
	}

	private function execute($scriptPath, array $arguments)
	{
		$argv = array_merge([$scriptPath], $arguments);

		ob_start();
		include($scriptPath);
		$output = ob_get_contents();
		ob_end_clean();

		return $output;
	}
}
