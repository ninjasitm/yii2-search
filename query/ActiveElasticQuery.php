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
	/**
	* Taken from \yi\db\Query
	* Sets the SELECT part of the query.
	* @param string|array $columns the columns to be selected.
	* Columns can be specified in either a string (e.g. "id, name") or an array (e.g. ['id', 'name']).
	* Columns can be prefixed with table names (e.g. "user.id") and/or contain column aliases (e.g. "user.id AS user_id").
	* The method will automatically quote the column names unless a column contains some parenthesis
	* (which means the column contains a DB expression).
	*
	* Note that if you are selecting an expression like `CONCAT(first_name, ' ', last_name)`, you should
	* use an array to specify the columns. Otherwise, the expression may be incorrectly split into several parts.
	*
	* When the columns are specified as an array, you may also use array keys as the column aliases (if a column
	* does not need alias, do not use a string key).
	*
	* @param string $option additional option that should be appended to the 'SELECT' keyword. For example,
	* in MySQL, the option 'SQL_CALC_FOUND_ROWS' can be used.
	* @return static the query object itself
	*/
	public function select($columns, $option = null)
	{
		//if (!is_array($columns)) {
		//	$columns = preg_split('/\s*,\s*/', trim($columns), -1, PREG_SPLIT_NO_EMPTY);
		//}
		/*$this->fields = $columns;
		if($columns[0] instanceof \yii\db\ColumnSchema)
		{
			print_r($columns);
			exit;
		}*/
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
					$value = ['order' => $value, 'ignore_unmapped' => true];
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
						'order' => SORT_ASC
					];
                }
            }
        }
        return $result;
    }
	

}
