<?php
namespace nitm\traits;

use nitm\helpers\Cache as RealCache;
use yii\helpers\ArrayHelper;

/**
 * Caching trait for models
 */
trait Cache {
	
	/**
	 * Get a cached model
	 * @param string $key
	 * @param string $property
	 * @param string $modelClass
	 * @return instanceof $modelClass
	 */
	public function getCachedModel($key, $modelClass=null, $property=null, $options=[])
	{
		//PHP Doesn't support serializing of Closure functions so using local object store
		//switch(static::$cache->exists($key))
		$ret_val = null;
		switch(RealCache::exists($key))
		{
			case true:
			$array = RealCache::getModel($key);
			try {
				$ret_val = new $array[0](array_filter($array[1]));
			} catch (\Exception $e) {
			}
			//$ret_val = static::$cache->get($key);
			break;
			
			default:
			switch(1)
			{
				case !is_null($property) && !is_null($modelClass):
				switch($this->hasProperty($property))
				{
					case true:
					$ret_val = \nitm\helpers\Relations::getRelatedRecord($property, $this, $modelClass, @$options['construct']);
					break;
					
					default:
					switch(isset($options['find']))
					{
						case true:
						$find = $modelClass::find();
						foreach($options['find'] as $option=>$params)
						{
							$find->$option($params);
						}
						unset($options['find']);
						$ret_val = $find->one();
						$ret_val = !$ret_val ? new $modelClass(@$options['construct']) : $ret_val;
						break;
						
						default:
						$ret_val = new $modelClass($options);
						break;
					}
					break;
				}
				//static::$cache->set($key, $ret_val, 1000);
				RealCache::setModel($key, [$modelClass, ArrayHelper::toArray($ret_val)]);
				break;
			}
			break;
		}
		return $ret_val;
	}
	
	/**
	 * Get a cached array
	 * @param string $key
	 * @param string $property
	 * @return array
	 */
	public function getCachedModelArray($key, $modelClass=null, $property=null, $options=[])
	{
		//PHP Doesn't support serializing of Closure functions so using local object store
		//switch(static::$cache->exists($key))
		$ret_val = [];
		switch(RealCache::exists($key))
		{
			case true:
			$array = RealCache::getModelArray($key);
			if(class_exists($array[0]))
			{
				foreach($array[1] as $values)
				{
					$ret_val[] = new $array[0]($values);
				}
			}
			else
				$ret_val = [];
			break;
			
			default:
			if(!is_null($property))
			{
				switch(1)
				{
					case array_key_exists($property, $this->getRelatedRecords()):
					$ret_val = $this->getRelatedRecords()[$property];
					break;
					
					case $this->hasProperty($property) && is_array($this->$property):
					$ret_val =  $this->$property;
					break;
					
					default:
					$ret_val = $options;
					break;
				}
				//static::$cache->set($key, $ret_val, 1000);
				RealCache::setModelArray($key, [$modelClass, ArrayHelper::toArray($ret_val)]);
				break;
			}
			break;
		}
		return $ret_val;
	}
	
	public function setCachedModel($key, $model)
	{
		RealCache::setModel($key, [$model->className(), $model]);
	}
	
	public function exists($key)
	{
		return RealCache::exists($key);
	}
}