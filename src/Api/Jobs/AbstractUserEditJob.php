<?php
abstract class AbstractUserEditJob extends AbstractUserJob
{
	protected $skipSaving = false;

	public function skipSaving()
	{
		$this->skipSaving = true;
		return $this;
	}
}
