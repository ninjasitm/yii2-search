<?php
namespace nitm\controllers\console\search;

class IndexerController extends \yii\console\Controller
{
	public $defaultAction = 'index';
	
	public $configPath;
	public $mode = 'feeder';
	// The indexer to use
	public $indexer;
	public $index;
	public $dataType;
	public $dataSource;
	
    public function actionIndex() 
	{
		if(file_exists($configFile = \Yii::getAlias($this->dataSource)))
			$config = require($configFile);
		else
			$config = [
				$this->dataType => explode(',', $this->dataSource)
			];
			
		switch($this->indexer)
		{
			case 'elasticsearch':
			$this->indexer = '\nitm\helpers\search\IndexerElasticsearch';
			break;
			
			default:
			$this->indexer = '\nitm\helpers\search\Indexer';
			break;
		}
		$model = new $this->indexer(array_merge([
			'mode' => $this->mode,
			'index' => $this->index,
		], $config));
		
		$model->prepare();
	}
	
    public function actionCheck($index, $type) 
	{
	}
	
	public function options($actionId)
	{
		return [
			'mode',
			'index',
			'indexer',
			'dataType',
			'dataSource'
		];
	}
}

?>