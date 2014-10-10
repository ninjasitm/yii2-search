<?php

namespace nitm\search;

use yii\helpers\ArrayHelper;
use nitm\models\DB;

/*
 * Class containing commong functions used by solr indexer and searcher class
 */
 
class BaseIndexer extends \yii\base\Behavior
{
	use traits\BaseIndexer;
	
	const AFTER_SEARCH_INDEX = 'afterIndex';
	const AFTER_SEARCH_UPDATE = 'afterUpdate';
	const AFTER_SEARCH_DELETE = 'afterDelete';
	const BEFORE_SEARCH_INDEX = 'afterIndex';
	const BEFORE_SEARCH_UPDATE = 'afterUpdate';
	const BEFORE_SEARCH_DELETE = 'afterDelete';
}
?>