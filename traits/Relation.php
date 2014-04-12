<?php
namespace nitm\traits;
/**
 * Traits defined for expanding active relation scopes until yii2 resolves traits issue
 */
 trait Relation {

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getUser()
    {
        return $this->hasOne(\nitm\models\User::className(), ['id' => 'user_id']);
    }
 }
?>
