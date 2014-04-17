<?php

namespace nitm\controllers;

class WidgetController extends \nitm\controllers\DefaultApiController
{
	/**
     * Finds the Category model based on its primary key value.
     * If the model is not found, a 404 HTTP exception will be thrown.
         * @param string $className
     * @param integer $id
     * @param array $with Load with what
     * @return the loaded model
     * @throws NotFoundHttpException if the model cannot be found
     */
    protected function findModel($className, $id, $with=null)
    {
        if ($id !== null && ($model = $className::find()->where(['id' => $id])) !== null) {
			$with = is_array($with) ? $with : (is_null($with) ? null : [$with]);
			switch(is_array($with))
			{
					case true:
					foreach($with as $w)
					{
							$model->with($w);
					}
					break;
			}
            return $model->one();
        } else {
            throw new NotFoundHttpException('The requested page does not exist.');
        }
    }
}
