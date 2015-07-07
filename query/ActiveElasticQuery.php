<?php
/**
 * @link http://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

namespace nitm\search\query;

use yii\elasticsearch\ActiveQuery;

/**
 * Adding extra support for vertain functions which the current ElasticSarch ActiveQuery does not provide
 *
 */
class ActiveElasticQuery extends ActiveQuery 
{
	public function select($fields) {
		$this->fields($fields);
		return $this;
	}
	
    public function normalizeOrderBy($columns)
    {
        if (is_array($columns)) {
			$result = [];
            foreach($columns as $key=>$value) {
				if(is_array($value))
					$value['ignore_unmapped'] = true;
				else
					$value = ['order' => (($value == SORT_DESC || $value == 'desc') ? 'desc' : 'asc'), 'ignore_unmapped' => true];
				$result[$key] = $value;
			};
        } else {
            $columns = preg_split('/\s*,\s*/', trim($columns), -1, PREG_SPLIT_NO_EMPTY);
            $result = [];
            foreach ($columns as $column) {
                if (preg_match('/^(.*?)\s+(asc|desc)$/i', $column, $matches)) {
                    $result[$matches[1]] = ['ignore_unmapped' => true];
                } else {
                    $result[$column] = [
						'ignore_unmapped' => true,
						'order' => 'desc'
					];
                }
            }
        }
        return $result;
    }
	

}
