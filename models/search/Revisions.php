<?php

namespace nitm\models\search;

use yii\base\Model;
use yii\data\ActiveDataProvider;
use nitm\models\Revisions as RevisionsModel;

/**
 * Revisions represents the model behind the search form about `nitm\models\Revisions`.
 */
class Revisions extends Model
{
    public $id;
    public $user_id;
    public $created_at;
    public $data;
    public $remote_type;
    public $remote_id;

    public function rules()
    {
        return [
            [['id', 'user_id', 'remote_id'], 'integer'],
            [['created_at', 'data', 'remote_type'], 'safe'],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'user_id' => 'User ID',
            'created_at' => 'Created At',
            'data' => 'Data',
            'remote_type' => 'Remote Type',
            'remote_id' => 'Remote ID',
        ];
    }

    public function search($params)
    {
        $query = RevisionsModel::find()->with('authorUser');
        $dataProvider = new ActiveDataProvider([
            'query' => $query,
        ]);

        if (!($this->load($params) && $this->validate())) {
            return $dataProvider;
        }

        $this->addCondition($query, 'id');
        $this->addCondition($query, 'user_id');
        $this->addCondition($query, 'created_at');
        $this->addCondition($query, 'data', true);
        $this->addCondition($query, 'remote_type', true);
        $this->addCondition($query, 'remote_id');
        return $dataProvider;
    }

    protected function addCondition($query, $attribute, $partialMatch = false)
    {
        if (($pos = strrpos($attribute, '.')) !== false) {
            $modelAttribute = substr($attribute, $pos + 1);
        } else {
            $modelAttribute = $attribute;
        }

        $value = $this->$modelAttribute;
        if (trim($value) === '') {
            return;
        }
        if ($partialMatch) {
            $query->andWhere(['like', $attribute, $value]);
        } else {
            $query->andWhere([$attribute => $value]);
        }
    }
}
