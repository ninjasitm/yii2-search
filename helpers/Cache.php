<?php

namespace nitm\helpers;

use yii\db\ActiveRecord;
use yii\base\Model;

/*
 * Setup model based caching, as PHP doesn't support serialization of Closures
 */
class Cache extends Model
{
	public static $cache;
	private static $_cache = [];
	
	/**
	 * Cache function that returns caching object
	 */
	public static function cache()
	{
		if(!isset(static::$cache))
		{
			static::$cache = \Yii::$app->hasProperty('cache') ? \Yii::$app->cache : new \yii\caching\FileCache;
		}
		return static::$cache;
	}
	
	public static function exists($key)
	{
		//return isset(static::$_cache[$key]);
		return static::$cache->exists($key);
	}
	
	public static function setModel($key, $model, $duration=5000)
	{
		//static::$_cache[$key] = $model;
		static::$cache->set($key, $model, $duration);
	}
	
	public static function setModelArray($key, $array)
	{
		static::setModel($key, $array);
	}
	
	/**
	 * Get a cached model
	 * @param string $key
	 * @return object
	 */
	public static function getModel($key)
	{
		$ret_val = null;
		if(static::$cache->exists($key))
			$ret_val = static::$cache->get($key);
		return $ret_val;
	}
	
	/**
	 * Get a cached array
	 * @param string $key
	 * @param string $property
	 * @return array
	 */
	public static function getModelArray($key, $property=null)
	{
		//PHP Doesn't support serializing of Closure functions so using local object store
		$array = [];
		//switch(isset(static::$_cache[$key]))
		if(static::$cache->exists($key))
			$ret_val = static::$cache->get($key);
		else
			$ret_val = [];
		return $ret_val;
	}
}
?>