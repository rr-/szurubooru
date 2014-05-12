<?php
interface IEntityRetriever
{
	public function __construct(IJob $job);
	public function tryRetrieve();
	public function retrieve();
	public function getRequiredArguments();
}
