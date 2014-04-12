<?php

namespace nitm\models\search;

use Yii;
use yii\base\Model;
use yii\data\ActiveDataProvider;
use nitm\models\Issues as IssuesModel;

/**
 * Issues represents the model behind the search form about `app\models\Issues`.
 */
class Issues extends IssuesModel
{
    public function rules()
    {
        return [
            [['id', 'parent_id', 'resolved', 'author', 'closed_by', 'resolved_by', 'closed', 'duplicate', 'duplicate_id'], 'integer'],
            [['parent_type', 'notes', 'created_at', 'resolved_on', 'closed_on'], 'safe'],
        ];
    }

    public function scenarios()
    {
        // bypass scenarios() implementation in the parent class
        return Model::scenarios();
    }

    public function search($params)
    {
        $query = IssuesModel::find();

        $dataProvider = new ActiveDataProvider([
            'query' => $query,
        ]);

        if (!($this->load($params) && $this->validate())) {
            return $dataProvider;
        }

        $query->andFilterWhere([
            'id' => $this->id,
            'parent_id' => $this->parent_id,
            'resolved' => $this->resolved,
            'created_at' => $this->created_at,
            'author' => $this->author,
            'closed_by' => $this->closed_by,
            'resolved_by' => $this->resolved_by,
            'resolved_on' => $this->resolved_on,
            'closed' => $this->closed,
            'closed_on' => $this->closed_on,
            'duplicate' => $this->duplicate,
            'duplicate_id' => $this->duplicate_id,
        ]);

        $query->andFilterWhere(['like', 'parent_type', $this->parent_type])
            ->andFilterWhere(['like', 'notes', $this->notes]);

        return $dataProvider;
    }
}
