<?php
class GetPropertyJobTest extends AbstractTest
{
	public function testRetrieval()
	{
		$key = PropertyModel::DbVersion;

		$ret = $this->assert->doesNotThrow(function() use ($key)
		{
			return Api::run(
				new GetPropertyJob(),
				[
					JobArgs::ARG_QUERY => $key,
				]);
		});

		$this->assert->areEqual(PropertyModel::get($key), $ret);
	}
}
