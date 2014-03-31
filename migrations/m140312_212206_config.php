<?php

use yii\db\Schema;

class m140312_212206_config extends \yii\db\Migration
{
	public $tableOptions = 'CHARACTER SET utf8 COLLATE utf8_general_ci ENGINE=InnoDB';
	
	private $_foreignOptions = [
		'onUpdate' => 'CASCADE',
		'onDelete' => 'RESTRICT'
	];
	private $_tables = [
		'config_containers' => [
			'fields' => [
				'id:PK' => '',
				'name:STRING' => "(64) NOT NULL",
				'author:INTEGER' => '',
				'editor:INTEGER' => '',
				'created_at:TIMESTAMP' => "DEFAULT CURRENT_TIMESTAMP",
				'updated_at:TIMESTAMP' => "ON UPDATE CURRENT_TIMESTAMP",
				'deleted:BOOLEAN' => ''
			],
			'index' => [
				'unique_name' => [
					'fields' => 'name',
					'unique' => true
				]
			],
			'foreignKeys' => [
				'foreignAuthor' => [
					'localKey' => 'author',
					'remote' => 'tbl_user',
					'remoteKey' => 'id',
				],
				'foreignEditor' => [
					'localKey' => 'editor',
					'remote' => 'tbl_user',
					'remoteKey' => 'id',
				]
			]
		],
		'config_sections' => [
			'fields' => [
				'id:PK' => '',
				'containerid:INTEGER' => '',
				'name:STRING' => "(64) NOT NULL",
				'author:INTEGER' => '',
				'editor:INTEGER' => '',
				'created_at:TIMESTAMP' => "DEFAULT CURRENT_TIMESTAMP",
				'updated_at:TIMESTAMP' => "ON UPDATE CURRENT_TIMESTAMP",
				'deleted:BOOLEAN' => ''
			],
			'index' => [
				'unique_name' => [
					'fields' => 'name',
					'unique' => true
				]
			],
			'foreignKeys' => [
				'foreignAuthor' => [
					'localKey' => 'author',
					'remote' => 'tbl_user',
					'remoteKey' => 'id',
				],
				'foreignEditor' => [
					'localKey' => 'editor',
					'remote' => 'tbl_user',
					'remoteKey' => 'id',
				],
				'foreignContainer' => [
					'localKey' => 'containerid',
					'remote' => 'config_containers',
					'remoteKey' => 'id',
				]
			]
		],
		'config_values' => [
			'fields' => [
				'id:PK' => '',
				'name:STRING' => "(64) NOT NULL",
				'value:TEXT' => "NULL",
				'comment:TEXT' => "NULL",
				'containerid:INTEGER' => '',
				'sectionid:INTEGER' => '',
				'author:INTEGER' => '',
				'editor:INTEGER' => '',
				'created_at:TIMESTAMP' => "DEFAULT CURRENT_TIMESTAMP",
				'updated_at:TIMESTAMP' => "ON UPDATE CURRENT_TIMESTAMP",
				'deleted:BOOLEAN' => ''
			],
			'index' => [
				'unique_name' => [
					'fields' => 'name',
					'unique' => true
				]
			],
			'foreignKeys' => [
				'foreignAuthor' => [
					'localKey' => 'author',
					'remote' => 'tbl_user',
					'remoteKey' => 'id',
				],
				'foreignEditor' => [
					'localKey' => 'editor',
					'remote' => 'tbl_user',
					'remoteKey' => 'id',
				],
				'foreignSection' => [
					'localKey' => 'sectionid',
					'remote' => 'config_sections',
					'remoteKey' => 'id',
				],
				'foreignContainer' => [
					'localKey' => 'containerid',
					'remote' => 'config_containers',
					'remoteKey' => 'id',
				]
			]
		],
	];
	
	public function up()
	{
		foreach($this->_tables as $table=>$schema)
		{
			$tableSchema = \Yii::$app->db->getTableSchema("$table");
			switch($tableSchema)
			{
				case false:
				foreach($schema['fields'] as $field=>$options)
				{
					$name = explode(':', $field);
					$fields[$name[0]] = constant('\yii\db\Schema::TYPE_'.$name[1]); 
				}
				$this->createTable("$table", $fields, $this->tableOptions);
				switch(isset($schema['index']))
				{
					case true:
					foreach($schema['index'] as $name=>$index)
					{
						$this->createIndex($name, "$table", $index['fields'], @$index['unique']);
					}
					break;
				}
				switch(isset($schema['foreignKeys']) && !empty($schema['foreignKeys']))
				{
					case true:
					foreach($schema['foreignKeys'] as $name=>$key)
					{
						switch(isset($key['options']))
						{
							case true:
							$key['options'] = array_merge($this->_foreignOptions, (array)$key['options']);
							break;
							
							default:
							$key['options'] = $this->_foreignOptions;
							break;
						}
						$this->addForeignKey($table.$name, "$table", $key['localKey'], $key['remote'], $key['remoteKey'], $key['options']['onDelete'], $key['options']['onUpdate']);
					}
					break;
				}
				break;
				
				default:
				switch(isset($schema['index']))
				{
					case true:
					foreach($schema['index'] as $name=>$index)
					{
						switch($index['fields'])
						{
							case Schema::TYPE_PK:
							$tableSchema->fixPrimaryKey($name);	
							break;
							
							default:
							$column = \Yii::$app->db->createCommand()->setSql("SHOW INDEX FROM ".$table." WHERE Key_name='$name'")->queryAll();
							switch(empty($column))
							{
								case true:
								$this->createIndex($name, "$table", $index['fields'], @$index['unique']);
								break;
							}
							break;
						}
					}
					break;
				}
				switch(isset($schema['foreignKeys']) && !empty($schema['foreignKeys']))
				{
					case true:
					/*$fk = $tableSchema->foreignKeys;
					switch(is_array($fk))
					{
						case true:
						//Drop all current foreign keys
						foreach($fk as $key)
						{
							print_r($key);
							foreach($key as $idx=>$val)
							{
								switch(is_array($val))
								{
									case true:
									echo "Dropping $idx";
									exit;
									$tableName = array_shift($val);
									$this->dropForeignKey($tableName.implode($val), "$table");
									break;
								}
							}
						}
						break;
					}*/
					//Recreate all foreign keys
					/*foreach($schema['foreignKeys'] as $name=>$key)
					{
						switch(isset($key['options']))
						{
							case true:
							$key['options'] = array_merge($this->_foreignOptions, (array)$key['options']);
							break;
							
							default:
							$key['options'] = $this->_foreignOptions;
							break;
						}
						$this->addForeignKey($table.$name, "$table", $key['localKey'], $key['remote'], $key['remoteKey'], $key['options']['onDelete'], $key['options']['onUpdate']);
					}*/
					break;
				}
				break;
			}
		}
		return true;
	}

	public function down()
	{
		/*
		 * Only delete if the tables are empty
		 */
		foreach(array_keys($this->_tables) as $table)
		{
			switch(!$this->getTableSchema("$table")->count())
			{
				case true:
				$this->dropTable("$table");
				break;
				
				default:
				echo "Not dropping '$table' becuase there is data. Empty this table first";
				break;
			}
		}
		return true;
	}
}
