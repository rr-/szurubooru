<?php
class ApiControllerTest extends AbstractTest
{
	public function testRunning()
	{
		Core::getConfig()->registration->needEmailForRegistering = false;
		Core::getConfig()->uploads->needEmailForUploading = false;

		$user = $this->userMocker->mockSingle();
		$this->grantAccess('addPost');
		$this->grantAccess('addPostTags');
		$this->grantAccess('addPostContent');

		$_GET =
		[
			'auth' => ['pass' => 'sekai', 'user' => $user->getName()],
			'name' => 'add-post',
			'args' => ['new-tag-names' => ['test', 'test2', 'test3']],
		];

		$tmpPath = tempnam(sys_get_temp_dir(), 'upload') . '.dat';
		copy($this->testSupport->getPath('image.jpg'), $tmpPath);

		Core::getContext()->transport = new StdClass;

		$_FILES =
		[
			'args' =>
			[
				'name' => ['new-post-content' => 'image.jpg'],
				'tmp_name' => ['new-post-content' => $tmpPath],
			],
		];

		ob_start();
		$apiController = new ApiController();
		$apiController->runAction();
		$output = ob_get_contents();
		ob_end_clean();

		$this->assert->areEqual(1, PostModel::getCount());
	}
}
