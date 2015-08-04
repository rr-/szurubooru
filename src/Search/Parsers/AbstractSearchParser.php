<?php
namespace Szurubooru\Search\Parsers;
use Szurubooru\Helpers\InputReader;
use Szurubooru\NotSupportedException;
use Szurubooru\Search\Filters\IFilter;
use Szurubooru\Search\Requirements\Requirement;
use Szurubooru\Search\Requirements\RequirementCompositeValue;
use Szurubooru\Search\Requirements\RequirementRangedValue;
use Szurubooru\Search\Requirements\RequirementSingleValue;
use Szurubooru\Search\Tokens\NamedSearchToken;
use Szurubooru\Search\Tokens\SearchToken;

abstract class AbstractSearchParser
{
    const ALLOW_COMPOSITE = 1;
    const ALLOW_RANGES = 2;

    public function createFilterFromInputReader(InputReader $inputReader)
    {
        $filter = $this->createFilter();
        $filter->setOrder($this->getOrder($inputReader->order, false) + $filter->getOrder());

        if ($inputReader->page)
        {
            $filter->setPageNumber($inputReader->page);
            $filter->setPageSize(25);
        }

        $tokens = $this->tokenize($inputReader->query);

        foreach ($tokens as $token)
        {
            if ($token instanceof NamedSearchToken)
            {
                if ($token->getKey() === 'order')
                    $filter->setOrder($this->getOrder($token->getValue(), $token->isNegated()) + $filter->getOrder());
                else
                    $this->decorateFilterFromNamedToken($filter, $token);
            }
            elseif ($token instanceof SearchToken)
                $this->decorateFilterFromToken($filter, $token);
            else
                throw new \RuntimeException('Invalid search token type: ' . get_class($token));
        }

        return $filter;
    }

    protected abstract function createFilter();

    protected abstract function decorateFilterFromToken(IFilter $filter, SearchToken $token);

    protected abstract function decorateFilterFromNamedToken(IFilter $filter, NamedSearchToken $namedToken);

    protected abstract function getOrderColumnMap();

    protected function createRequirementValue($text, $flags = 0, callable $valueDecorator = null)
    {
        if ($valueDecorator === null)
        {
            $valueDecorator = function($value)
            {
                return $value;
            };
        }

        if ((($flags & self::ALLOW_RANGES) === self::ALLOW_RANGES) && substr_count($text, '..') === 1)
        {
            list ($minValue, $maxValue) = explode('..', $text);
            $minValue = $valueDecorator($minValue);
            $maxValue = $valueDecorator($maxValue);
            $tokenValue = new RequirementRangedValue();
            $tokenValue->setMinValue($minValue);
            $tokenValue->setMaxValue($maxValue);
            return $tokenValue;
        }
        else if ((($flags & self::ALLOW_COMPOSITE) === self::ALLOW_COMPOSITE) && strpos($text, ',') !== false)
        {
            $values = explode(',', $text);
            $values = array_map($valueDecorator, $values);
            $tokenValue = new RequirementCompositeValue();
            $tokenValue->setValues($values);
            return $tokenValue;
        }

        $value = $valueDecorator($text);
        return new RequirementSingleValue($value);
    }

    protected function addRequirementFromToken($filter, $token, $type, $flags, callable $valueDecorator = null)
    {
        $requirement = new Requirement();
        $requirement->setType($type);
        $requirement->setValue($this->createRequirementValue($token->getValue(), $flags, $valueDecorator));
        $requirement->setNegated($token->isNegated());
        $filter->addRequirement($requirement);
    }

    private function getOrderColumn($tokenText)
    {
        $map = $this->getOrderColumnMap();

        foreach ($map as $item)
        {
            list ($aliases, $value) = $item;
            if ($this->matches($tokenText, $aliases))
                return $value;
        }

        throw new NotSupportedException('Unknown order term: ' . $tokenText
            . '. Possible order terms: '
            . join(', ', array_map(function($term) { return join('/', $term[0]); }, $map)));
    }

    private function getOrder($query, $negated)
    {
        $order = [];
        $tokens = array_filter(preg_split('/\s+/', trim($query)));

        foreach ($tokens as $token)
        {
            $token = preg_split('/,|\s+/', $token);
            $orderToken = $token[0];

            if (count($token) === 1)
            {
                $orderDir = IFilter::ORDER_DESC;
            }
            elseif (count($token) === 2)
            {
                if ($token[1] === 'desc')
                    $orderDir = IFilter::ORDER_DESC;
                elseif ($token[1] === 'asc')
                    $orderDir = IFilter::ORDER_ASC;
                else
                    throw new \Exception('Wrong search order direction');
            }
            else
                throw new \Exception('Wrong search order token');

            $orderColumn = $this->getOrderColumn($orderToken);
            if ($orderColumn === null)
                throw new \InvalidArgumentException('Invalid search order token: ' . $orderToken);

            if ($negated)
            {
                $orderDir = $orderDir == IFilter::ORDER_DESC ? IFilter::ORDER_ASC : IFilter::ORDER_DESC;
            }


            $order[$orderColumn] = $orderDir;
        }

        return $order;
    }

    private function tokenize($query)
    {
        $searchTokens = [];

        foreach (array_filter(preg_split('/\s+/', trim($query))) as $tokenText)
        {
            $negated = false;
            if (substr($tokenText, 0, 1) === '-')
            {
                $negated = true;
                $tokenText = substr($tokenText, 1);
            }

            $colonPosition = strpos($tokenText, ':');
            if (($colonPosition !== false) && ($colonPosition > 0))
            {
                $searchToken = new NamedSearchToken();
                list ($tokenKey, $tokenValue) = explode(':', $tokenText, 2);
                $searchToken->setKey($tokenKey);
                $searchToken->setValue($tokenValue);
            }
            else
            {
                $searchToken = new SearchToken();
                $searchToken->setValue($tokenText);
            }

            $searchToken->setNegated($negated);
            $searchTokens[] = $searchToken;
        }

        return $searchTokens;
    }

    protected function matches($text, $array)
    {
        $text = $this->transformText($text);
        foreach ($array as $elem)
        {
            if ($this->transformText($elem) === $text)
                return true;
        }
        return false;
    }

    protected function transformText($text)
    {
        return str_replace('_', '', strtolower($text));
    }

}
