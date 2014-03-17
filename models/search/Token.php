<?php

namespace nitm\module\models\search;

use yii\base\Model;
use yii\data\ActiveDataProvider;

/**
 * Token represents the model behind the search form about Token.
 */
class Token extends Model
{
	public $tokenid;
	public $userid;
	public $token;
	public $added;
	public $active;
	public $level;
	public $revoked;
	public $revoked_on;

	public function rules()
	{
		return [
			[['tokenid', 'userid'], 'integer'],
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
			'tokenid' => 'Tokenid',
			'userid' => 'Userid',
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
	 $query = \nitm\module\models\api\Token::find();
		$dataProvider = new ActiveDataProvider([
			'query' => $query,
		]);
		
		if (!($this->load($params) && $this->validate())) {
			return $dataProvider;
		}

		$this->addCondition($query, 'userid');
		$this->addCondition($query, 'userid', true);
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
