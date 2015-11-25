<?php
namespace Szurubooru\Search\ParserConfigs;
use Szurubooru\NotSupportedException;
use Szurubooru\Search\Requirements\Requirement;
use Szurubooru\Search\Requirements\RequirementCompositeValue;
use Szurubooru\Search\Requirements\RequirementRangedValue;
use Szurubooru\Search\Requirements\RequirementSingleValue;
use Szurubooru\Search\Tokens\NamedSearchToken;
use Szurubooru\Search\Tokens\SearchToken;

abstract class AbstractSearchParserConfig
{
    const ALLOW_COMPOSITE = 1;
    const ALLOW_RANGE = 2;

    private $orderAliasMap = [];
    private $basicTokenParser = null;
    private $namedTokenParsers = [];
    private $specialTokenParsers = [];

    public abstract function createFilter();

    public function getColumnForTokenValue($tokenValue)
    {
        $map = $this->orderAliasMap;

        foreach ($map as $item)
        {
            list ($aliases, $value) = $item;
            if (self::matches($tokenValue, $aliases))
                return $value;
        }

        throw new NotSupportedException('Unknown order term: ' . $tokenValue
            . '. Possible order terms: '
            . join(', ', array_map(function($term) { return join('/', $term[0]); }, $map)));
    }

    public function getRequirementForBasicToken(SearchToken $token)
    {
        if ($this->basicTokenParser)
        {
            $tmp = $this->basicTokenParser;
            return $tmp($token);
        }
        throw new NotSupportedException('Basic tokens are not valid in this search');
    }

    public function getRequirementForNamedToken(NamedSearchToken $token)
    {
        if (self::matches($token->getKey(), ['special']))
        {
            foreach ($this->specialTokenParsers as $item)
            {
                if (!self::matches($token->getValue(), $item->aliases))
                    continue;

                $tmp = $item->callback;
                return $tmp($token);
            }

            $this->raiseNamedTokenError($token->getValue(), $this->specialTokenParsers);
        }

        if ((strpos($token->getKey(), 'min') !== false
            || strpos($token->getKey(), 'max') !== false)
            && strpos($token->getValue(), '..') === false)
        {
            foreach ($this->namedTokenParsers as $item)
            {
                if (is_callable($item->flagsOrCallback) ||
                    !($item->flagsOrCallback & self::ALLOW_RANGE))
                {
                    continue;
                }

                foreach ($item->aliases as $alias)
                {
                    if (!self::matches($token->getKey(), [$alias . '_min', $alias . '_max']))
                        continue;

                    $pseudoToken = new NamedSearchToken();
                    $pseudoToken->setKey($alias);
                    $pseudoToken->setValue(strpos($token->getKey(), 'min') !== false
                        ? $token->getValue() . '..'
                        : '..' . $token->getValue());
                    return $this->getRequirementForNamedToken($pseudoToken);
                }
            }
        }

        foreach ($this->namedTokenParsers as $item)
        {
            if (!self::matches($token->getKey(), $item->aliases))
                continue;

            if (is_callable($item->flagsOrCallback))
            {
                $tmp = $item->flagsOrCallback;
                $requirementValue = $tmp($token->getValue());
            }
            else
            {
                $requirementValue = $this->createRequirementValue(
                    $token->getValue(),
                    $item->flagsOrCallback);
            }

            $requirement = new Requirement();
            $requirement->setType($item->columnName);
            $requirement->setValue($requirementValue);
            $requirement->setNegated($token->isNegated());
            return $requirement;
        }

        $this->raiseNamedTokenError($token->getKey(), $this->namedTokenParsers);
    }

    protected function defineOrder($columnName, array $aliases)
    {
        $this->orderAliasMap []= [$aliases, $columnName];
    }

    protected function defineBasicTokenParser($parser)
    {
        $this->basicTokenParser = $parser;
    }

    protected function defineNamedTokenParser(
        $columnName,
        array $aliases,
        $flagsOrCallback = 0)
    {
        $item = new \StdClass;
        $item->columnName = $columnName;
        $item->aliases = $aliases;
        $item->flagsOrCallback = $flagsOrCallback;
        $this->namedTokenParsers []= $item;
    }

    protected function defineSpecialTokenParser(
        array $aliases,
        $callback)
    {
        $item = new \StdClass;
        $item->aliases = $aliases;
        $item->callback = $callback;
        $this->specialTokenParsers []= $item;
    }

    protected static function createRequirementValue(
        $text, $flags = 0)
    {
        if (($flags & self::ALLOW_RANGE) === self::ALLOW_RANGE
            && substr_count($text, '..') === 1)
        {
            list ($minValue, $maxValue) = explode('..', $text);
            $value = new RequirementRangedValue();
            $value->setMinValue($minValue);
            $value->setMaxValue($maxValue);
            return $value;
        }

        if (($flags & self::ALLOW_COMPOSITE) === self::ALLOW_COMPOSITE
            && strpos($text, ',') !== false)
        {
            $values = explode(',', $text);
            $value = new RequirementCompositeValue();
            $value->setValues($values);
            return $value;
        }

        return new RequirementSingleValue($text);
    }

    protected function createDateRequirementValue($value)
    {
        if (substr_count($value, '..') === 1)
        {
            list ($dateMin, $dateMax) = explode('..', $value);
            $timeMin = self::convertDateTime($dateMin)[0];
            $timeMax = self::convertDateTime($dateMax)[1];
        }
        else
        {
            $date = $value;
            list ($timeMin, $timeMax) = self::convertDateTime($date);
        }

        $value = new RequirementRangedValue();
        $value->setMinValue(date('c', $timeMin));
        $value->setMaxValue(date('c', $timeMax));
        return $value;
    }

    protected static function matches($text, $array)
    {
        $text = self::transformText($text);
        foreach ($array as $elem)
        {
            if (self::transformText($elem) === $text)
                return true;
        }
        return false;
    }

    private static function transformText($text)
    {
        return str_replace('_', '', strtolower($text));
    }

    private static function convertDateTime($value)
    {
        $value = strtolower(trim($value));
        if (!$value)
        {
            return null;
        }
        elseif ($value === 'today')
        {
            $timeMin = mktime(0, 0, 0);
            $timeMax = mktime(24, 0, -1);
        }
        elseif ($value === 'yesterday')
        {
            $timeMin = mktime(-24, 0, 0);
            $timeMax = mktime(0, 0, -1);
        }
        elseif (preg_match('/^(\d{4})$/', $value, $matches))
        {
            $year = intval($matches[1]);
            $timeMin = mktime(0, 0, 0, 1, 1, $year);
            $timeMax = mktime(0, 0, -1, 1, 1, $year + 1);
        }
        elseif (preg_match('/^(\d{4})-(\d{1,2})$/', $value, $matches))
        {
            $year = intval($matches[1]);
            $month = intval($matches[2]);
            $timeMin = mktime(0, 0, 0, $month, 1, $year);
            $timeMax = mktime(0, 0, -1, $month + 1, 1, $year);
        }
        elseif (preg_match('/^(\d{4})-(\d{1,2})-(\d{1,2})$/', $value, $matches))
        {
            $year = intval($matches[1]);
            $month = intval($matches[2]);
            $day = intval($matches[3]);
            $timeMin = mktime(0, 0, 0, $month, $day, $year);
            $timeMax = mktime(0, 0, -1, $month, $day + 1, $year);
        }
        else
            throw new \Exception('Invalid date format: ' . $value);

        return [$timeMin, $timeMax];
    }

    private function raiseNamedTokenError($key, array $parsers)
    {
        if (empty($parsers))
            throw new NotSupportedException('Such search is not supported in this context.');

        throw new NotSupportedException(
            'Unknown search key: ' . $key
            . '. Possible search keys: '
            . join(', ', array_map(function($item) { return join('/', $item->aliases); }, $parsers)));
    }
}
