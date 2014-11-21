<?php
namespace Szurubooru\Routes\Tags;
use Szurubooru\Helpers\InputReader;
use Szurubooru\Privilege;
use Szurubooru\Services\PrivilegeService;
use Szurubooru\Services\TagService;

class MergeTags extends AbstractTagRoute
{
	private $privilegeService;
	private $tagService;
	private $inputReader;

	public function __construct(
		PrivilegeService $privilegeService,
		TagService $tagService,
		InputReader $inputReader)
	{
		$this->privilegeService = $privilegeService;
		$this->tagService = $tagService;
		$this->inputReader = $inputReader;
	}

	public function getMethods()
	{
		return ['PUT'];
	}

	public function getUrl()
	{
		return '/api/tags/:tagName/merge';
	}

	public function work()
	{
		$tagName = $this->getArgument('tagName');
		$targetTagName = $this->inputReader->targetTag;
		$sourceTag = $this->tagService->getByName($tagName);
		$targetTag = $this->tagService->getByName($targetTagName);
		$this->privilegeService->assertPrivilege(Privilege::MERGE_TAGS);
		return $this->tagService->mergeTag($sourceTag, $targetTag);
	}
}
