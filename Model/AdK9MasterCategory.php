<?php

/*
 * Copyright 2017 SPCVN Co., Ltd.
 * All right reserved.
*/

/**
 * @Author: Naoki Kiyosawa
 * @Date:   2017-10-31 17:38:35
 */

App::uses('AppModel', 'Model');

/**
 * Application model for Cake.
 *
 * Add your application-wide methods in the class below, your models
 * will inherit them.
 *
 * @package       app.Model
 */
class AdK9MasterCategory extends AppModel{

    var $name = "K9MasterCategory";
    var $useTable = "k9_master_category";
    var $primaryKey = "id";
	var $useDbConfig="default";

	public function getFoodCategories()
	{

		return $this->getCategories("food");
	}

	public function getDrinkCategories()
	{

		return $this->getCategories("drink");
	}

	public function getSpaCategories()
	{

		return $this->getCategories("spa");
	}

	public function getCategories($type){

		$w=null;
		$w["and"]["{$this->name}.type"]=$type;
		$order=array("{$this->name}.position ASC");
		return $this->find("all",array(
		
			"conditions"=>$w,
			"order"     =>$order
		));
	}

	public function getCategoriesByAliase($aliases=array())
	{
		$conditions=array();
		$conditions["and"]["K9MasterCategory.aliase"]=$aliases;
		$categories=$this->find("all",array( "conditions"=>$conditions ));
		return $categories;
	}

}
