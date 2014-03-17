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
				'id' => Schema::TYPE_PK,
				'name' => Schema::TYPE_STRING."(64) NOT NULL",
				'author' => Schema::TYPE_INTEGER,
				'editor' => Schema::TYPE_INTEGER,
				'created_at' => Schema::TYPE_TIMESTAMP." default NOW()",
				'updated_at' => Schema::TYPE_TIMESTAMP." on update NOW()",
				'deleted' => Schema::TYPE_BOOLEAN
			],
			'index' => [
				'unique_name' => [
					'fields' => 'name',
					'unique' = true
				]
			],
			'foreignKeys' => [
				'foreignAuthor' => [
					'localKey' => 'author',
					'remote' => 'tbl_user',
					'remoteKey' = 'id',
				],
				'foreignEditor' => [
					'localKey' => 'editor',
					'remote' => 'tbl_user',
					'remoteKey' = 'id',
				]
			]
		],
		'config_sections' => [
			'fields' => [
				'id' => Schema::TYPE_PK,
				'containerid' => Schema::TYPE_INTEGER,
				'name' => Schema::TYPE_STRING."(64) NOT NULL",
				'author' => Schema::TYPE_INTEGER,
				'editor' => Schema::TYPE_INTEGER,
				'created_at' => Schema::TYPE_TIMESTAMP." default NOW()",
				'updated_at' => Schema::TYPE_TIMESTAMP." on update NOW()",
				'deleted' => Schema::TYPE_BOOLEAN
			],
			'index' => [
				'unique_name' => [
					'fields' => 'name',
					'unique' = true
				]
			],
			'foreignKeys' => [
				'foreignAuthor' => [
					'localKey' => 'author',
					'remote' => 'tbl_user',
					'remoteKey' = 'id',
				],
				'foreignEditor' => [
					'localKey' => 'editor',
					'remote' => 'tbl_user',
					'remoteKey' = 'id',
				],
				'foreignContainer' => [
					'localKey' => 'containerid',
					'remote' => 'config_containers',
					'remoteKey' = 'id',
				]
			]
		],
		'config_values' => [
			'fields' => [
				'id' => Schema::TYPE_PK,
				'name' => Schema::TYPE_STRING."(64) NOT NULL",
				'value' => Schema::TYPE_TEXT." NULL",
				'comment' => Schema::TYPE_TEXT." NULL",
				'containerid' => Schema::TYPE_INTEGER,
				'author' => Schema::TYPE_INTEGER,
				'editor' => Schema::TYPE_INTEGER,
				'created_at' => Schema::TYPE_TIMESTAMP." default NOW()",
				'updated_at' => Schema::TYPE_TIMESTAMP." on update NOW()",
				'deleted' => Schema::TYPE_BOOLEAN
			],
			'index' => [
				'unique_name' => [
					'fields' => 'name',
					'unique' = true
				]
			],
			'foreignKeys' => [
				'foreignAuthor' => [
					'localKey' => 'author',
					'remote' => 'tbl_user',
					'remoteKey' = 'id',
				],
				'foreignEditor' => [
					'localKey' => 'editor',
					'remote' => 'tbl_user',
					'remoteKey' = 'id',
				],
				'foreignSection' => [
					'localKey' => 'sectionid',
					'remote' => 'config_sections',
					'remoteKey' = 'id',
				],
				'foreignContainer' => [
					'localKey' => 'containerid',
					'remote' => 'config_containers',
					'remoteKey' = 'id',
				]
			]
		],
	];
	
	public function up()
	{
		foreach($this->_tables as $table=>$schema)
		{
			switch($this->tableExists("{{%$table}}"))
			{
				case false:
				$this->createTable("{{%$table}}", $schema['fields'], $this->tableOptions);
				switch(isset($schema['index']))
				{
					case true:
					foreach($schema['index'] as $name=>$index)
					{
						$this->createIndex($name, "{{%$table}}", $index['fields'], @$index['unique']);
					}
					break;
				}
				switch(isset($schema['foreignKeys']) && !empty($schema['foreignKeys']))
				{
					case true:
					foreach($schema['foreignKeys'] as $name=>$index)
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
						$this->addForeignKey($name, "{{%$table}}", $key['localKey'], "{{%".$key['remote']."}}", $key['remoteKey'], $key['options']['onDelete'], $key['options']['onUpdate']);
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
							$this->getTableSchema("{{%$table}}")->fixPrimaryKey($name);
							break;
							
							default:
							$this->createIndex($name, "{{%$table}}", $index['fields'], @$index['unique']);
							break;
						}
					}
					break;
				}
				switch(isset($schema['foreignKeys']) && !empty($schema['foreignKeys']))
				{
					case true:
					$fk = $this->getTableSchema("{{%$table}}")->foreignKeys;
					switch(is_array($fk))
					{
						case true:
						//Drop all current foreign keys
						foreach($fk as $key)
						{
							foreach($key as $idx=>$val)
							{
								switch(is_array($val))
								{
									case true:
									$this->dropForeignKey($idx, "{{%$table}}");
									break;
								}
							}
						}
						break;
					}
					//Recreate all foreign keys
					foreach($schema['foreignKeys'] as $name=>$index)
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
						$this->addForeignKey($name, "{{%$table}}", $key['localKey'], "{{%".$key['remote']."}}", $key['remoteKey'], $key['options']['onDelete'], $key['options']['onUpdate']);
					}
					break;
				}
				break;
			}
		}
	}

	public function down()
	{
		/*
		 * Only delete if the tables are empty
		 */
		foreach(array_keys($this->_tables) as $table)
		{
			switch(!$this->getTableSchema("{{%$table}}")->count())
			{
				case true:
				$this->dropTable("{{%$table}}");
				break;
				
				default:
				echo "Not dropping '$table' becuase there is data. Empty this table first";
				break;
			}
		}
		return false;
	}
}
