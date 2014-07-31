<?php
use \Chibi\Sql as Sql;

class TagSearchParser extends AbstractSearchParser
{
	protected function processSetup(&$tokens)
	{
		$allowedSafety = array_map(
			function($safety)
			{
				return $safety->toInteger();
			},
			Access::getAllowedSafety());
		$this->statement
			->addInnerJoin('post_tag', Sql\Functors::equals('tag.id', 'post_tag.tag_id'))
			->addInnerJoin('post', Sql\Functors::equals('post.id', 'post_tag.post_id'))
			->setCriterion(Sql\Functors::conjunction()
				->add(Sql\Functors::in('safety', Sql\Binding::fromArray($allowedSafety))))
			->setGroupBy('tag.id');
	}

	protected function processSimpleToken($value, $neg)
	{
		if ($neg)
			return false;

		if (strlen($value) >= 3)
			$value = '%' . $value;
		$value .= '%';

		$this->statement->getCriterion()
			->add(Sql\Functors::noCase(Sql\Functors::like('tag.name', new Sql\Binding($value))));

		return true;
	}

	protected function processOrderToken($orderByString, $orderDir)
	{
		if ($orderByString == 'popularity')
			$this->statement->setOrderBy('post_count', $orderDir);
		elseif ($orderByString == 'creation_date')
			$this->statement->setOrderBy('tag.creation_date', $orderDir);
		elseif ($orderByString == 'update_date')
			$this->statement->setOrderBy('tag.update_date', $orderDir);
		elseif ($orderByString == 'alpha')
			$this->statement->setOrderBy(Sql\Functors::{'case'}('tag.name'), $orderDir);
		else
			return false;
		return true;
	}
}
