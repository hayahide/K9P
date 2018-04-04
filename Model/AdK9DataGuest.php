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
class AdK9DataGuest extends AppModel{

    var $name       ="K9DataGuest";
    var $useTable   ="k9_data_guests";
    var $primaryKey ="id";
	var $useDbConfig="default";

	var $association=array(

		"hasMany"=>array(

			'K9DataReservation' => array(

				'className' => 'K9DataReservation',
				'foreignKey' => 'guest_id'
			),
		),
	);

	function beforeSave($option=array(),$data=array()){

		$this->data["K9DataGuest"]=array_map("trim",$this->data["K9DataGuest"]);
		if(isset($this->data["K9DataGuest"]["first_name"]))  $this->data["K9DataGuest"]["first_name"] =toHan($this->data["K9DataGuest"]["first_name"]);
		if(isset($this->data["K9DataGuest"]["last_name"]))   $this->data["K9DataGuest"]["last_name"]  =toHan($this->data["K9DataGuest"]["last_name"]);

		return parent::beforeSave();
	}

	function getGuestInformationWithHash($guest_hash,$params=array()){

		$w=null;
		$w["and"]["{$this->name}.hash"]=$guest_hash;
		$w["and"]["{$this->name}.del_flg"]=0;
		return $this->find("first",array(

			"conditions"=>$w,
		));
	}

	function searchGuestByNames($firstname,$lastname,$params=array()){

		$w=null;
		if(isset($params["del_flg"]) AND is_numeric($params["del_flg"])) $w["and"]["del_flg"]=$params["del_flg"];
		if(!empty($firstname)) $w["and"]["`first_name` collate utf8_unicode_ci like"]="%{$firstname}%";
		if(!empty($lastname))  $w["and"]["`last_name` collate utf8_unicode_ci like"]="%{$lastname}%";
		return $this->find("all",array(
		
			"conditions"=>$w
		));
	}

}
