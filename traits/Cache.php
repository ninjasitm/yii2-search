<?php
namespace nitm\traits;

use nitm\helpers\Cache as RealCache;

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
	public function getCachedModel($key, $property=null, $modelClass=null, $options=[])
	{
		//PHP Doesn't support serializing of Closure functions so using local object store
		//switch(static::$cache->exists($key))
		$ret_val = null;
		switch(RealCache::exists($key))
		{
			case true:
			$ret_val = RealCache::getModel($key);
			//$ret_val = static::$cache->get($key);
			break;
			
			default:
			switch(1)
			{
				case !is_null($property) && !is_null($modelClass):
				switch($this->hasProperty($property))
				{
					case true:
					$ret_val = is_a($this->$property, $modelClass::className()) ? $this->$property : new $modelClass($options);
					break;
					
					default:
					$ret_val = new $modelClass($options);
					break;
				}
				//static::$cache->set($key, $ret_val, 1000);
				RealCache::setModel($key, $ret_val);
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
	public function getCachedModelArray($key, $property=null, $options=[])
	{
		//PHP Doesn't support serializing of Closure functions so using local object store
		//switch(static::$cache->exists($key))
		$ret_val = [];
		switch(RealCache::exists($key))
		{
			case true:
			$ret_val = RealCache::getModel($key);
			//$ret_val = static::$cache->get($key);
			break;
			
			default:
			switch(1)
			{
				case !is_null($property):
				$ret_val = $options;
				if($this->hasProperty($property))
				{
					$ret_val = is_array($this->$property) ? $this->$property : $ret_val;
				}
				//static::$cache->set($key, $ret_val, 1000);
				RealCache::setModel($key, $ret_val);
				break;
			}
			break;
		}
		return $ret_val;
	}
	
	public function setCachedModel($key, $model)
	{
		RealCache::setModel($key, $model);
	}
	
	public function inCache($key)
	{
		return RealCache::exists($key);
	}
}