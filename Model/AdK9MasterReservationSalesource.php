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
class AdK9MasterReservationSalesource extends AppModel{

    var $name = "K9MasterReservationSalesource";
    var $useTable = "k9_master_reservation_salesourcies";
    var $primaryKey = "id";
	var $useDbConfig="default";

	function getSaleSource()
	{
		$lang=Configure::read('Config.language');
		$name=$this->hasField("name_{$lang}")?"name_{$lang}":"name_jpn";
		$data=$this->find("all",array( "order"=>array("{$this->name}.position ASC")));
		$data=Set::combine($data,"{n}.{$this->name}.id","{n}.{$this->name}.{$name}");
		return $data;
	}

}
