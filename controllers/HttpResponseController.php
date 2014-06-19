<?php

namespace nitm\controllers;

class HttpResponseController extends \nitm\module\controllers\DefaultController
{
    public function actionIndex()
    {
        return $this->render('index');
    }

}
