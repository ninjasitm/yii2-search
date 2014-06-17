<?php

namespace nitm\models\search;

use Yii;
use yii\base\Model;
use yii\data\ActiveDataProvider;
use nitm\models\Replies as RepliesModel;

/**
 * Replies represents the model behind the search form about `app\models\Replies`.
 */
class Replies extends BaseSearch
{	
    public $id;
    public $author;
    public $editor;
    public $created_at;
	public $updated_at;
	public $email;
    public $message;
    public $parent_type;
    public $parent_id;
    public $parent_key;
    public $hidden;
    public $disabled;
    public $public;
    public $ip_host;
    public $ip_addr;
    public $reply_to;
    public $reply_to_author;
	
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

    public function search($params, $with=[])
    {
		return parent::search(RepliesModel::className(), $params, $with);
    }

    protected function getConditions()
    {
       return [
			['id'],
			['parent_id'],
			['parent_type', true],
			['parent_key', true],
			['created_at', true],
			['updated_at', true],
			['email', true],
			['reply_to', true],
			['reply_to_author', true],
			['author'],
			['editor'],
			['message', true],
			['ip_addr', true],
			['ip_host', true],
			['hidden'],
			['disabled'],
			['public'],
        ];
    }
}
