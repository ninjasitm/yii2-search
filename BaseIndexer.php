<?php

namespace nitm\search;

use yii\helpers\ArrayHelper;
use nitm\models\DB;

/*
 * Class containing commong functions used by solr indexer and searcher class
 */

class BaseIndexer extends \yii\base\Object
{
	use traits\BaseIndexerTrait;

	const AFTER_SEARCH_INDEX = 'afterIndex';
	const AFTER_SEARCH_UPDATE = 'afterUpdate';
	const AFTER_SEARCH_DELETE = 'afterDelete';
	const AFTER_SEARCH_PREPARE = 'afterPrepare';
	const BEFORE_SEARCH_INDEX = 'beforeIndex';
	const BEFORE_SEARCH_UPDATE = 'beforeUpdate';
	const BEFORE_SEARCH_DELETE = 'beforeDelete';
	const BEFORE_SEARCH_PREPARE = 'beforePrepare';
}
?>
