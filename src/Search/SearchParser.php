<?php
namespace Szurubooru\Search;
use Szurubooru\Helpers\InputReader;
use Szurubooru\Search\Filters\IFilter;
use Szurubooru\Search\ParserConfigs\AbstractSearchParserConfig;
use Szurubooru\Search\Tokens\NamedSearchToken;
use Szurubooru\Search\Tokens\SearchToken;

class SearchParser
{
    private $parserConfig;

    public function __construct(AbstractSearchParserConfig $parserConfig)
    {
        $this->parserConfig = $parserConfig;
    }

    public function createFilterFromInputReader(InputReader $inputReader)
    {
        $filter = $this->parserConfig->createFilter();
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
                {
                    $filter->setOrder(
                        $this->getOrder($token->getValue(), $token->isNegated())
                        + $filter->getOrder());
                }
                else
                {
                    $requirement = $this->parserConfig->getRequirementForNamedToken($token);
                    $filter->addRequirement($requirement);
                }
            }
            elseif ($token instanceof SearchToken)
            {
                $requirement = $this->parserConfig->getRequirementForBasicToken($token);
                $filter->addRequirement($requirement);
            }
            else
            {
                throw new \RuntimeException('Invalid search token type: ' . get_class($token));
            }
        }

        return $filter;
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

            $orderColumn = $this->parserConfig->getColumnForTokenValue($orderToken);
            if ($orderColumn === null)
                throw new \InvalidArgumentException('Invalid search order token: ' . $orderToken);

            if ($negated)
            {
                $orderDir = $orderDir === IFilter::ORDER_DESC
                    ? IFilter::ORDER_ASC
                    : IFilter::ORDER_DESC;
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
}
