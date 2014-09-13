<?php
namespace nitm\controllers\console\search;

class IndexerController extends \yii\console\Controller
{
	public $defaultAction = 'index';
	
	/**
	 * The mode for the indexer
	 */
	public $mode = 'feeder';
	
	/**
	 * The indexer to use
	 */
	public $indexer;
	
	/**
	 * The index to work on
	 */
	public $index;
	
	/**
	 * Should we re-isert all items?
	 */
	public $reIndex;
	
	/**
	 * The data type
	 */
	public $dataType;
	
	/**
	 * The data source. Either a string or
	 */
	public $dataSource;
	
	/**
	 * Verbosity
	 * 0 - No output
	 * 1 = Summary output
	 * 2 = Summary output + Item outpout
	 */
	public $verbose;
	
	/**
	 * Only do a dry run and don't actually index
	 */
	public $mock;
	
	protected $model;
	
	private $_config;
	
	public function initParameters()
	{
		$this->getIndexer();
		$this->_config = $this->prepareDataSource();
		$this->model = new $this->indexer(array_merge([
			'mode' => $this->mode,
			'index' => $this->index,
			'reIndex' => isset($this->reIndex) ? true : false,
			'verbose' => $this->verbose,
			'mock' => isset($this->mock) ? true : false
		], $this->_config));
	}
	
    public function actionIndex() 
	{
		$this->initParameters();
		$this->model->operation('index');
		return 0;
	}
	
	public function actionUpdate()
	{
		$this->initParameters();
		$this->model->operation('update');
		return 0;
	}
	
    public function actionDelete() 
	{
		$this->initParameters();
		$this->model->opertation('delete');
		return 0;
	}
	
	public function options($actionId)
	{
		return [
			'mode',
			'index',
			'indexer',
			'dataType',
			'dataSource',
			'verbose',
			'mock',
			'reIndex'
		];
	}
	
	/**
	 * Determine where the config information comes form
	 * @return array $config;
	 */
	protected function prepareDatasource()
	{
		if(file_exists($configFile = \Yii::getAlias($this->dataSource)))
			$config = require($configFile);
		else 
		{
			echo "Not using a config file\nDatasource is ".$this->dataSource."\n";
			if(!isset($this->dataType))
				die("The dataType value must be set if not using a config file\n");
			else if(!isset($this->dataSource))
				die("No dataSource set!\n");
		}
			
		if(!isset($config))
			$config = [
				'_'.$this->dataType => explode(',', $this->dataSource)
			];
		return $config;
	}
	
	/**
	 * Determine where the indexer to user
	 * @return string $idexer Class;
	 */
	protected function getIndexer()
	{
		switch($this->indexer)
		{
			case 'elasticsearch':
			$this->indexer = '\nitm\helpers\search\IndexerElasticsearch';
			break;
			
			default:
			$this->indexer = '\nitm\helpers\search\Indexer';
			break;
		}
		return $this->indexer;
	}
}

?>