<?php
namespace nitm\models\search;

/*
 * Th ebase ElasticSearch model that is used to search Elasticsearch datastores
 */
 
class BaseElasticSearch extends \nitm\helpers\search\BaseElasticSearch
{
	use \nitm\traits\Query, 
	\nitm\traits\Relations;

    protected function addCondition($attribute, $value, $partialMatch=false)
    {
        if (($pos = strrpos($attribute, '.')) !== false) {
            $modelAttribute = substr($attribute, $pos + 1);
        } else {
            $modelAttribute = $attribute;
        }
        if (is_string($value) && trim($value) === '') {
            return;
        }
		switch(1)
		{
			case is_numeric($value) && !$partialMatch:
			case is_bool($value) && !$partialMatch:
			case is_array($value) && !$partialMatch:
            switch($this->inclusiveSearch && !$this->exclusiveSearch)
			{
				case true:
				$this->conditions['or'][] = [$attribute => $value];
				break;
				
				default:
				$this->conditions['and'][] = [$attribute => $value];
				break;
			}
			break;
			
			default:
			switch($partialMatch) 
			{
				case true:
				switch($this->inclusiveSearch)
				{
					case true:
					$this->conditions['or'][] = [$attribute, $value, false];
					break;
					
					default:
					$this->conditions['and'][] = [$attribute, $value, false];
					break;
				}
				break;
				
				default:
            	$this->conditions['and'][] = [$attribute => $value];
				break;
			}
			break;
		}
	}
	
	protected function getTextParam($value)
	{
		//$this->dataProvider->query->query = ['query_string' => $value];
		$this->useEmptyParams = true;
		return ['q' => $value];
	}
}
?>