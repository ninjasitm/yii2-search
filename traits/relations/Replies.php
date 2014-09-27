<?php
namespace nitm\traits\relations;

/**
 * Traits defined for expanding active relation scopes until yii2 resolves traits issue
 */

trait Replies {
	
	/**
	 * Return the reply author_id information
	 * @param string $what The property to return
	 */
	public function getReplyTo()
	{
		return $this->hasOne(\nitm\models\Replies::className(), ['id' => 'reply_to'])->with('author');
	}
}
?>
