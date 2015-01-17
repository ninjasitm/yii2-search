<?php
/**
 * @link http://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

namespace nitm\search\query;

use yii\base\Component;
use yii\db\QueryInterface;
use yii\db\QueryTrait;
use yii\helpers\Json;
use Yii;

/**
 * Query represents Mongo "find" operation.
 *
 * Query provides a set of methods to facilitate the specification of "find" command.
 * These methods can be chained together.
 *
 * For example,
 *
 * ~~~
 * $query = new Query;
 * // compose the query
 * $query->select(['name', 'status'])
 *     ->from('customer')
 *     ->limit(10);
 * // execute the query
 * $rows = $query->all();
 * ~~~
 *
 * @property Collection $collection Collection instance. This property is read-only.
 *
 * @author Paul Klimov <klimov.paul@gmail.com>
 * @since 2.0
 */
class ActiveMongoQuery extends \yii\mongodb\ActiveQuery
{

    /**
     * Builds the Mongo cursor for this query.
     * @param Connection $db the database connection used to execute the query.
     * @return \MongoCursor mongo cursor instance.
     */
    protected function buildCursor($db = null)
    {
        $cursor = $this->getCollection($db)->find([], []);
        if (!empty($this->orderBy)) {
            $cursor->sort($this->composeSort());
        }
        $cursor->limit($this->limit);
        $cursor->skip($this->offset);
		
		return $cursor;
    }
}
