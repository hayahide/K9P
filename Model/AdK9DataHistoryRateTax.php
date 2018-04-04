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
class AdK9DataHistoryRateTax extends AppModel{

    var $name = "K9DataHistoryRateTax";
    var $useTable = "k9_data_history_rate_tax";
    var $primaryKey = "id";
	var $useDbConfig="default";

	public function getTax($params=array())
	{

		$conditions=array();
		$conditions["and"]["{$this->name}.del_flg"]=0;
		$recursive=isset($params["recursive"])?$params["recursive"]:1;
		$order=array("{$this->name}.enter_date DESC");

		$data=$this->find("all",array(
		
			"recursive"=>$recursive,
			"order"    =>$order
		));

		return $data;
	}

}
