<?php

namespace nitm\models\search;

use Yii;
use yii\base\Model;
use yii\data\ActiveDataProvider;
use nitm\models\Replies as RepliesModel;

/**
 * Replies represents the model behind the search form about `app\models\Replies`.
 */
class Replies extends RepliesModel
{
    public function rules()
    {
        return [
            [['id', 'parent_id', 'hidden', 'disabled', 'public', 'author'], 'integer'],
            [['parent_type', 'message', 'created_at', 'ip_addr', 'ip_host', 'email'], 'safe'],
        ];
    }

    public function scenarios()
    {
        // bypass scenarios() implementation in the parent class
        return Model::scenarios();
    }

    public function search($params)
    {
        $query = RepliesModel::find();

        $dataProvider = new ActiveDataProvider([
            'query' => $query,
        ]);
		$params = isset($params[$this->formName()]) ? $params : [$this->formName() => $params];
        if (!($this->load($params) && $this->validate())) {
            return $dataProvider;
        }

        $query->andFilterWhere([
            'id' => $this->id,
            'parent_id' => $this->parent_id,
            'parent_key' => $this->parent_key,
            'created_at' => $this->created_at,
            'reply_to' => $this->reply_to,
            'reply_to_author' => $this->reply_to_author,
            'author' => $this->author,
        ]);

        $query->andFilterWhere(['like', 'parent_type', $this->parent_type])
            ->andFilterWhere(['like', 'message', $this->message]);

        return $dataProvider;
    }
}
