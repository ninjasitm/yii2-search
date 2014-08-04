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
		return isset(static::$_cache[$key]);
	}
	
	public static function setModel($key, $model)
	{
		static::$_cache[$key] = $model;
	}
	
	public static function setModelArray($key, $array)
	{
		static::$_cache[$key] = $array;
	}
	
	/**
	 * Get a cached model
	 * @param string $key
	 * @return object
	 */
	public static function getModel($key)
	{
		//PHP Doesn't support serializing of Closure functions so using local object store
		//switch(static::$cache->exists($key))
		$ret_val = null;
		switch(isset(static::$_cache[$key]))
		{
			case true:
			$ret_val = static::$_cache[$key];
			//$ret_val = static::$cache->get($key);
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
	public static function getModelArray($key, $property=null)
	{
		//PHP Doesn't support serializing of Closure functions so using local object store
		//switch(static::$cache->exists($key))
		$ret_val = [];
		switch(isset(static::$_cache[$key]))
		{
			case true:
			$ret_val = static::$_cache[$key];
			//$ret_val = static::$cache->get($key);
			break;
			
			default:
			switch(1)
			{
				case !is_null($property):
				$ret_val = is_array(static::$property) ? static::$property : [];
				//static::$cache->set($key, $ret_val, 1000);
				static::$_cache[$key] = $ret_val;
				break;
			}
			break;
		}
		return $ret_val;
	}
}
?>