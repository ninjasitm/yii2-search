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
		try {
			$module = \Yii::$app->getModule('nitm-search');
			if(is_object($module))
			{
				$indexer = $module->getIndexer();
				$attributes = $indexer::normalize($event->sender->findOne($event->sender->getId())->toArray());
				$attributes['_md5'] = $module->fingerprint($attributes);
				$options = [
					'url' => $this->isWhat().'/'.$event->sender->getId(), 
					json_encode($attributes), 
					true
				];
				return $indexer::api('put', $options);
			}
			return false;
		} catch \Exception $e {
			//Only throw this error if we're debugging
			if(YII_DEBUG)
				throw $e;
		}
	}
}
?>