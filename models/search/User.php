<?php

namespace nitm\module\models\search;

use yii\base\Model;
use yii\data\ActiveDataProvider;

/**
 * User represents the model behind the search form about User.
 */
class User extends Model
{
	public $id;
	public $username;
	public $password;
	public $f_name;
	public $l_name;
	public $email;
	public $status;
	public $role;
	public $term;

	public function rules()
	{
		return [
			[['id', 'status',  'role'], 'integer'],
			[['username', 'email'], 'safe'],
			[['term'], 'required', 'on' => ['apiSearch']],
		];
	}
	
	public function scenarios ()
	{
		return [
			'default' => ['username', 'email', 'f_name', 'l_name', 'role', 'status'],
			'apiSearch' => ['term']
		];
	}

	/**
	 * @inheritdoc
	 */
	public function attributeLabels()
	{
		return [
			'id' => 'ID',
			'username' => 'Username',
			'email' => 'Email',
			'status' => 'Status',
			'f_name' => 'First Name',
			'l_name' => 'Last Name',
		];
	}

	public function search($params)
	{
		$query = \nitm\module\models\User::find();
		$dataProvider = new ActiveDataProvider([
			'query' => $query,
		]);
		switch($this->getScenario())
		{
			case 'apiSearch':
			$dataProvider->query->select(['id', 'username', '(SELECT name FROM `tbl_profile` WHERE user_id=id) AS name']);
			$this->setAttributes($params);
			if($this->validate())
			{
				$searchFields = array_fill(0, sizeof($this->getAttributes()), $this->term);
				$this->setAttributes(array_combine(array_keys($this->getAttributes()), $searchFields), false);
				foreach($this->attributes() as $attr)
				{
					switch($attr)
					{
						case 'role':
						case 'status':
						switch($attr)
						{
							case 'role':
							$method = 'getRoles';
							break;
							
							case 'status':
							$method = 'getStatuses';
							break;
						}
						$match = forward_static_call([\nitm\module\models\User::className(), $method]);
						$value = array_keys(preg_grep("/^".$this->$attr."/i", $match));
						$this->$attr = array_shift($value);
						break;
					}
				}
			}
			break;
			
			default:
			if (!($this->load($params) && $this->validate())) {
				return $dataProvider;
			}
			break;
		}

		$this->addCondition($query, 'username');
		$this->addCondition($query, 'username', true);
		$this->addCondition($query, 'f_name');
		$this->addCondition($query, 'f_name', true);
		$this->addCondition($query, 'l_name');
		$this->addCondition($query, 'l_name', true);
		$this->addCondition($query, 'status');
		$this->addCondition($query, 'status', true);
		$this->addCondition($query, 'role');
		$this->addCondition($query, 'role', true);
		$this->addCondition($query, 'email');
		$this->addCondition($query, 'email', true);
		//$this->addCondition($query, ['name' => $this->term, 'value' => '(SELECT name FROM `tbl_profile`.`name` WHERE name="'.$this->term.'")', 'operator' => 'in']);
		$this->addCondition($query,  ['name' => '', 'value' => '(SELECT 1 FROM `tbl_profile` WHERE (LOWER(`tbl_profile`.`name`) LIKE "%'.strtolower($this->term).'%") OR (`tbl_profile`.`public_email` LIKE "%'.$this->term.'") OR (`tbl_profile`.`gravatar_email` LIKE "%'.$this->term.'") AND `tbl_profile`.`user_id`=id)', 'operator' => 'expression'], true);
		return $dataProvider;
	}

	protected function addCondition($query, $attribute, $partialMatch = false)
	{
		switch(is_array($attribute))
		{
			case true:
			$value = $attribute['value'];
			$operator = isset($attribute['operator']) ? $attribute['operator'] : 'like';
			$attribute = $attribute['name'];
			break;
			
			default:
			$value = $this->$attribute;
			$operator = 'like';
			break;
		}
		if (trim($value) === '') {
			return;
		}
		if ($partialMatch) {
			switch($operator)
			{
				case 'like':
				$value = strtr($value, ['%'=>'\%', '_'=>'\_', '\\'=>'\\\\']);
				$query->orWhere([$operator, $attribute, $value]);
				break;
				
				case 'in':
				$query->orWhere([$operator, $attribute, $value]);
				break;
				
				case 'expression':
				$query->orWhere($value);
				break;
			}
		} else {
			$query->orWhere([$attribute => $value]);
		}
	}
}
