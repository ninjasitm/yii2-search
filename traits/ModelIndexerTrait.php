<?php

namespace nitm\search\traits;

use yii\helpers\ArrayHelper;
use nitm\models\DB;

/*
 * Class containing commong functions used by solr indexer and searcher class
 */
 
trait ModelIndexerTrait
{
	public $disableSearchIndexing = false;
	
	protected function updateSearchEntry($event)
	{
		if($this->disableSearchIndexing)
			return;
			
		try {
			$module = \Yii::$app->getModule('nitm-search');
			if(is_object($module))
			{
				$indexer = $module->getIndexer();
				$attributes = $indexer::normalize($event->sender->getAttributes(), false, $event->sender->getTableSchema()->columns);
				
				$attributes['_md5'] = $module->fingerprint($attributes);
				$options = [
					'url' => $event->sender->isWhat().'/'.$event->sender->getId(), 
					json_encode($attributes), 
					true
				];
				return $indexer::api('put', $options);
			}
			return false;
		} catch (\Exception $e) {
			//Only throw this error if we're debugging
			if(YII_DEBUG)
				throw $e;
		}
	}
	
	protected function handleSearchRecord(&$event)
	{
		return $event;
	}
}
?>