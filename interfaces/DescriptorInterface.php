<?php

namespace nitm\module\interfaces;

/**
	This interface returns custom values based on certain scenarios
*/

interface DescriptorInterface 
{		
	//Return the columns we need when used in a form
	public static function formColumns();
	
	//Return the columns we need when we're editing
	public static function editColumns();
	
	//Return the columns we need when we're adding
	public static function addColumns();
	
	//groupby
	public static function filters();
	
	//rules not static according to Yii Model
	public function rules();
	
	//scenariosnot static according to Yii Model
	public function scenarios();
}

?> 
