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
class AdK9DataPriceRoomType extends AppModel{

    var $name = "K9DataPriceRoomType";
    var $useTable = "k9_data_price_room_types";
    var $primaryKey = "id";
	var $useDbConfig="default";

	public $belongsTo = array(

		'K9MasterRoomType' => array(
			'className' => 'K9MasterRoomType',
			'foreignKey' => 'room_type_id',
		),
		'K9MasterEmployee' => array(

			'className' => 'K9MasterEmployee',
			'foreignKey' => 'final_employee_entered',
			'conditions' => array("K9MasterEmployee.del_flg"=>'0'),
		),
	);

	function getEffectCurrentPlan($base_date){

		$w=null;
		$w["and"]["DATE_FORMAT(K9DataPriceRoomType.end,'%Y%m%d') >= "]=$base_date;
		$w["and"]["{$this->name}.del_flg"]=0;
		return $this->find("all",array(
		
			"conditions"=>$w,
			"recursive"=>2,
			"order"=>array("K9DataPriceRoomType.end DESC")
		));
	}

	function getDataByRoomIdWithRelationDays($room_type_ids=array(),$start_date,$end_date){

		$w=null;
		$w["and"]["K9DataPriceRoomType.room_type_id"]=$room_type_ids;
		$w["and"]["K9DataPriceRoomType.del_flg"]=0;
		$w["or"][0]["DATE_FORMAT(K9DataPriceRoomType.start,'%Y%m%d') <= "]=$start_date;
		$w["or"][0]["DATE_FORMAT(K9DataPriceRoomType.end,'%Y%m%d') >= "]  =$start_date;
		$w["or"][1]["DATE_FORMAT(K9DataPriceRoomType.start,'%Y%m%d') <= "]=$end_date;
		$w["or"][1]["DATE_FORMAT(K9DataPriceRoomType.end,'%Y%m%d') >= "]  =$end_date;
		$w["or"][2]["DATE_FORMAT(K9DataPriceRoomType.start,'%Y%m%d') >= "]=$start_date;
		$w["or"][2]["DATE_FORMAT(K9DataPriceRoomType.end,'%Y%m%d') <= "] =$end_date;
		return $this->findAll($w);
	}

	function removePlan($data_id){

		$this->id=$data_id;
		$save["id"]=$data_id;
		$save["del_flg"]=1;
		return $this->save($save);
	}


}
