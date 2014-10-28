<?php

namespace nitm\search\traits;

use yii\helpers\ArrayHelper;
use nitm\models\DB;

/*
 * Class containing commong functions used by solr indexer and searcher class
 */
 
trait ModelIndexerTrait
{
	protected function updateSearchEntry($event)
	{
		$module = \Yii::$app->getModule('nitm-search');
		$indexer = $module->getIndexer();
		$attributes = $indexer::normalize($event->sender->getDirtyAttributes());
		$attributes['_md5'] = $module->fingerprint($attributes);
		$options = [
			'url' => $this->isWhat().'/'.$event->sender->getId(), 
			json_encode($attributes), 
			true
		];
		$indexer::api('put', $options);
	}
}
?>