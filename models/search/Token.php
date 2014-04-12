<?php

namespace nitm\models\search;

use yii\base\Model;
use yii\data\ActiveDataProvider;

/**
 * Token represents the model behind the search form about Token.
 */
class Token extends Model
{
	public $id;
	public $user_id;
	public $token;
	public $added;
	public $active;
	public $level;
	public $revoked;
	public $revoked_on;

	public function rules()
	{
		return [
			[['id', 'user_id'], 'integer'],
			[['token', 'added', 'level', 'revoked_on'], 'safe'],
			[['active', 'revoked'], 'boolean'],
		];
	}

	/**
	 * @inheritdoc
	 */
	public function attributeLabels()
	{
		return [
			'id' => 'Token Id',
			'user_id' => 'User Id',
			'token' => 'Token',
			'added' => 'Added',
			'active' => 'Active',
			'level' => 'Level',
			'revoked' => 'Revoked',
			'revoked_on' => 'Revoked On',
		];
	}

	public function search($params)
	{
	 $query = \nitm\models\api\Token::find()->with('user');
		$dataProvider = new ActiveDataProvider([
			'query' => $query,
		]);
		
		if (!($this->load($params) && $this->validate())) {
			return $dataProvider;
		}

		$this->addCondition($query, 'user_id');
		$this->addCondition($query, 'user_id', true);
		$this->addCondition($query, 'token');
		$this->addCondition($query, 'token', true);
		$this->addCondition($query, 'level');
		$this->addCondition($query, 'level', true);
		return $dataProvider;
	}

	protected function addCondition($query, $attribute, $partialMatch = false)
	{
		$value = $this->$attribute;
		if (trim($value) === '') {
			return;
		}
		if ($partialMatch) {
			$value = '%' . strtr($value, ['%'=>'\%', '_'=>'\_', '\\'=>'\\\\']) . '%';
			$query->andWhere(['like', $attribute, $value]);
		} else {
			$query->andWhere([$attribute => $value]);
		}
	}
}
