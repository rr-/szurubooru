<?php
class PropertyModelTest extends AbstractTest
{
	public function testGetAndSet()
	{
		$this->assert->doesNotThrow(function()
		{
			PropertyModel::set(PropertyModel::FeaturedPostId, 100);
		});
		$this->assert->areEqual(100, PropertyModel::get(PropertyModel::FeaturedPostId));
	}

	public function testGetAndSetWithArbitraryKeys()
	{
		$this->assert->doesNotThrow(function()
		{
			PropertyModel::set('something', 100);
		});
		$this->assert->areEqual(100, PropertyModel::get('something'));
	}
}
