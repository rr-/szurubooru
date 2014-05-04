<?php
abstract class AbstractPostEditJob extends AbstractPostJob
{
	protected $skipSaving = false;

	public function skipSaving()
	{
		$this->skipSaving = true;
		return $this;
	}
}
