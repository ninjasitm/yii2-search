<?php

namespace nitm\helpers\search;

use yii\helpers\ArrayHelper;
use nitm\models\DB;

/*
 * Class containing commong functions used by solr indexer and searcher class
 */
 
interface IndexerInterface
{	
	/**
	 * This function is called to prepare the data for the operation
	 */
	public function prepare();
	
	/**
	 * get the data from an SQL statement
	 */
	public function prepareFromSql();
	
	/**
	 * Get the data from a list of model classes
	 */
	public function prepareFromClasses();
	
	/**
	 * Get the data from a list of tables
	 */
	public function prepareFromTables();
	
	/**
	 * Add entries to the index
	 */
	public function operationIndex();
	
	/**
	 * Perform a delete of indexes
	 */
	public function operationDelete();
	
	/**
	 * Perform an update on the indexes
	 */
	public function operationUpdate();
	
	/**
	 * The function that commits the operation
	 */
	public function api($operation, $options);
}
?>