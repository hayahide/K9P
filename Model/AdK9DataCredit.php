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
class AdK9DataCredit extends AppModel{

    var $name = "K9DataCredit";
    var $useTable = "k9_data_credits";
    var $primaryKey = "id";
	var $useDbConfig="default";

	public $belongsTo = array(

		'K9DataReservation' => array(

			'className'  => 'K9DataReservation',
			'foreignKey' => 'reserve_id',
			'conditions' => array('K9DataReservation.del_flg' => '0'),
			'dependent'  => false,
		),
		'K9MasterEmployee' => array(

			'className'  => 'K9MasterEmployee',
			'foreignKey' => 'final_employee_entered',
			'conditions' => array('K9MasterEmployee.del_flg' => '0'),
			'dependent'  => false,
		),
	);

	public function insertCredits($data,$reserve_id,$employee_id,$params=array(),$inserts=array())
	{

		if(empty($data)) return $inserts;
		foreach($data as $day=>$price) break;
		unset($data[$day]);

		$res=$this->insertCredit($price,$reserve_id,$day,$employee_id);
		if(empty($res)) return false;

		$inserts=array_merge($res,$inserts);
		return $this->insertCredits($data,$reserve_id,$employee_id,$params,$inserts);
	}

	public function insertCredit($data,$reserve_id,$day,$employee_id,$inserts=array())
	{

		if(empty($data)) return $inserts;
		$__data=array_shift($data);

		$save["reserve_id"]=$reserve_id;
		$save["price"]       =$__data["price"];
		$save["remarks"]     =$__data["remarks"];
		$save["cash_type_id"]=$__data["cash_type_id"];
		$save["final_employee_entered"]=$employee_id;
		$save["enter_date"]  =$day;
		$this->id=null;
		if(!$res=$this->save($save)) return false;
		$inserts[]=$res;
		return $this->insertCredit($data,$reserve_id,$day,$employee_id,$inserts);
	}

	public function removeCredits($data,$employee_id)
	{
		$update=array();

		foreach($data as $k=>$menu_id){
		
			$count=count($update);
			$update[$count]["id"]   =$menu_id;
			$update[$count]["final_employee_entered"]=$employee_id;
			$update[$count]["del_flg"]=1;
		}

		try{
		
			$this->multiInsert($update);

		}catch(Exception $e){
		
			$res["status"] =false;
			$res["message"]=$e->getMessage();
			return $res;
		}

		$res["status"]=true;
		return $res;
	}

	public function updateCredits($data,$employee_id)
	{
		$update=array();

		foreach($data as $menu_id=>$v){
		
			$count=count($update);
			$update[$count]["id"]   =$menu_id;
			$update[$count]["price"]=$v["price"];
			$update[$count]["remarks"]=$v["remarks"];
			$update[$count]["cash_type_id"]=$v["cash_type_id"];
			$update[$count]["final_employee_entered"]=$employee_id;
		}

		try{
		
			$this->multiInsert($update);

		}catch(Exception $e){
		
			$res["status"] =false;
			$res["message"]=$e->getMessage();
			return $res;
		}

		$res["status"]=true;
		return $res;
	}

}
