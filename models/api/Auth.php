<?php

namespace nitm\models\api;

class Auth extends \yii\db\ActiveRecord
{
	public static function tableName()
	{
		return 'access_tokens';
	}
	
	public function validate()
	{
		$ret_val = false;
        $request = \Yii::$app->getComponent('request');
		$headers = $request->getHeaders();
		switch($headers['Authorization'])
		{
			case false:
			//Do authentication with token and verify that token is valid for use
			//$token = array_pop(explode(' ', $headers['Authorization']));
			$token = ['c1e48dd56b43196a06a66b67ec3bede6', ''];
			$ret_val = Token::find()->where([
				'token' => $token[0],
				'identity' => $token[1]
				
			])->exists();
			break;
		}
		return $ret_val;
	}
}
?>