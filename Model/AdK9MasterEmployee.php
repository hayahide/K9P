<?php

/*
 * Copyright 2017 SPCVN Co., Ltd.
 * All right reserved.
*/

/**
 * @Author: Naoki Kiyosawa
 * @Date:   2017-10-31 17:38:35
 */

App::uses('AppModel','Model');

/**
 * Application model for Cake.
 *
 * Add your application-wide methods in the class below, your models
 * will inherit them.
 *
 * @package       app.Model
 */

class AdK9MasterEmployee extends AppModel{

    var $name = "K9MasterEmployee";
    var $useTable = "k9_master_employees";
    var $primaryKey = "id";
	var $useDbConfig="default";

	public static $AUTH_MASTER="master";
	public static $AUTH_CHIEF ="chief";
	public static $AUTH_STAFF ="staff";

	public static $JOBTITLE_COOKING     ="cooking";
	public static $JOBTITLE_FRONT       ="front";
	public static $JOBTITLE_MANAGER     ="manager";
	public static $JOBTITLE_HOUSEKEEPER ="housekeeper";
	public static $JOBTITLE_SERVICE     ="service";

	var $association=array(
	
		"hasOne"=>array(
			
			'K9MasterEmployeeAccount' => array(

				'className' => 'K9MasterEmployeeAccount',
				'foreignKey' => 'employee_id'
			),
			"K9DataEmployeeAttendance" => array(
			
				'className' => 'K9DataEmployeeAttendance',
				'foreignKey' => 'employee_id'
			)
		),
	);

	function getEmployees($del_flg=0,$params=array()){

		$w=null;
		if(is_numeric($del_flg)) $w["and"]["{$this->name}.del_flg"]=$del_flg;
		return $this->find("all",$params);
	}

	function getEmployeeByHash($hash,$params=array()){

		$employee=$this->association["hasOne"]["K9MasterEmployeeAccount"];
		$this->hasOne["K9MasterEmployeeAccount"]=$employee;

		$w=null;
		$w["and"]["{$this->name}.hash"]=$hash;
		return $this->find("first",$params);
	}

	function getMembersByThedayHired($day)
	{

		$conditions=array();
		$conditions["and"]["DATE_FORMAT({$this->name}.created,'%Y%m%d') <= "]=$day;
		$conditions["and"]["{$this->name}.del_flg"]=0;
		$data=$this->find("all",array(
		
			"conditions"=>$conditions
		));

		return $data;
	}

}
