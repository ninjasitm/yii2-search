<?php

namespace nitm\controllers;

class FormController extends \nitm\module\controllers\DefaultController
{
    public function actionIndex()
    {
        return $this->render('index');
    }

}
