<?php
interface ITestRunner
{
	public function setFilter($filter);
	public function setTestsPath($testsPath);
	public function setEnvironmentPrepareAction($callback);
	public function setEnvironmentCleanAction($callback);
	public function setTestWrapperAction($callback);
	public function run();
}
