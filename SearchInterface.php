<?php

namespace nitm\search;

interface SearchInterface
{
	const SEARCH_PARAM = '__searchType';
	const SEARCH_PARAM_BOOL = '__searchIncl';
	const SEARCH_FULLTEXT = 'text';
	const SEARCH_NORMAL = 'default';
}
?>