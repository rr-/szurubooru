<?php
class Model_Property extends RedBean_SimpleModel
{
	const FeaturedPostId = 0;
	const FeaturedPostUserId = 1;
	const FeaturedPostDate = 2;

	static $allProperties = null;

	public static function get($propertyId)
	{
		if (self::$allProperties === null)
		{
			self::$allProperties = [];
			foreach (R::find('property') as $prop)
			{
				self::$allProperties[$prop->prop_id] = $prop->value;
			}
		}
		return isset(self::$allProperties[$propertyId])
			? self::$allProperties[$propertyId]
			: null;
	}

	public static function set($propertyId, $value)
	{
		$row = R::findOne('property', 'prop_id = ?', [$propertyId]);
		if (!$row)
		{
			$row = R::dispense('property');
			$row->prop_id = $propertyId;
		}
		$row->value = $value;
		self::$allProperties[$propertyId] = $value;
		R::store($row);
	}
}
