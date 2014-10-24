<?php
namespace nitm\search;

/*
 * Th ebase ElasticSearch model that is used to search Elasticsearch datastores
 */
 
class ElasticSearch extends BaseElasticSearch
{
	use \nitm\traits\Query, 
	\nitm\traits\Relations;
}
?>