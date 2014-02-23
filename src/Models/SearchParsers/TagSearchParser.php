<?php
class TagSearchParser extends AbstractSearchParser
{
	protected function processSetup(&$tokens)
	{
		$allowedSafety = PrivilegesHelper::getAllowedSafety();
		$this->statement
			->addInnerJoin('post_tag', new SqlEqualsOperator('tag.id', 'post_tag.tag_id'))
			->addInnerJoin('post', new SqlEqualsOperator('post.id', 'post_tag.post_id'))
			->setCriterion((new SqlConjunction)->add(SqlInOperator::fromArray('safety', SqlBinding::fromArray($allowedSafety))))
			->groupBy('tag.id');
	}

	protected function processSimpleToken($value, $neg)
	{
		if ($neg)
			return false;

		if (strlen($value) >= 3)
			$value = '%' . $value;
		$value .= '%';

		$this->statement->getCriterion()->add(new SqlNoCaseOperator(new SqlLikeOperator('tag.name', new SqlBinding($value))));
		return true;
	}

	protected function processOrderToken($orderByString, $orderDir)
	{
		if ($orderByString == 'popularity')
			$this->statement->setOrderBy('post_count', $orderDir);
		elseif ($orderByString == 'alpha')
			$this->statement->setOrderBy('tag.name', $orderDir);
		else
			return false;
		return true;
	}
}
