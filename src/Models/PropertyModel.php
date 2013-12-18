<?php
class PropertyModel implements IModel
{
	const FeaturedPostId = 0;
	const FeaturedPostUserName = 1;
	const FeaturedPostDate = 2;
	const DbVersion = 3;

	static $allProperties = null;
	static $loaded = false;

	public static function getTableName()
	{
		return 'property';
	}

	public static function loadIfNecessary()
	{
		if (!self::$loaded)
		{
			self::$loaded = true;
			self::$allProperties = [];
			$query = (new SqlQuery())->select('*')->from('property');
			foreach (Database::fetchAll($query) as $row)
				self::$allProperties[$row['prop_id']] = $row['value'];
		}
	}

	public static function get($propertyId)
	{
		self::loadIfNecessary();
		return isset(self::$allProperties[$propertyId])
			? self::$allProperties[$propertyId]
			: null;
	}

	public static function set($propertyId, $value)
	{
		self::loadIfNecessary();
		Database::transaction(function() use ($propertyId, $value)
		{
			$row = Database::query((new SqlQuery)
				->select('id')
				->from('property')
				->where('prop_id = ?')
				->put($propertyId));

			$query = (new SqlQuery);

			if ($row)
			{
				$query
					->update('property')
					->set('value = ?')
					->put($value)
					->where('prop_id = ?')
					->put($propertyId);
			}
			else
			{
				$query
					->insertInto('property')
					->open()->raw('prop_id, value_id')->close()
					->open()->raw('?, ?')->close()
					->put([$propertyId, $value]);
			}

			Database::query($query);

			self::$allProperties[$propertyId] = $value;
		});
	}
}
