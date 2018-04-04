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
class AdK9DataUnavailableRoom extends AppModel{

    var $name = "K9DataUnavailableRoom";
    var $useTable = "k9_data_unavailable_rooms";
    var $primaryKey = "id";
	var $useDbConfig="default";

	public $belongsTo = array(

		'K9MasterRoomSituation' => array(

			'className' => 'K9MasterRoomSituation',
			'foreignKey' => 'reason_id'
		),
	);

	private function __addRange(&$conditions,$start,$end,$format="")
	{
		if(empty($format)) $format="%Y%m%d";
		$conditions["and"]["or"][0]["and"]["DATE_FORMAT({$this->name}.start_date,'{$format}') <= "]=$start;
		$conditions["and"]["or"][0]["and"]["DATE_FORMAT({$this->name}.end_date,'{$format}') >= "]  =$start;
		$conditions["and"]["or"][1]["and"]["DATE_FORMAT({$this->name}.start_date,'{$format}') <= "]=$end;
		$conditions["and"]["or"][1]["and"]["DATE_FORMAT({$this->name}.end_date,'{$format}') >= "]  =$end;
		$conditions["and"]["or"][2]["and"]["DATE_FORMAT({$this->name}.start_date,'{$format}') > "] =$start;
		$conditions["and"]["or"][2]["and"]["DATE_FORMAT({$this->name}.end_date,'{$format}') < "]   =$end;
	}

	public function getDataBasedonRange($start,$end,$params=array())
	{
		$conditions=array();
		if(isset($params["room_id"]) AND is_numeric($params["room_id"]))   $conditions["and"]["{$this->name}.room_id"]=$params["room_id"];
		if(isset($params["exclusive"]) AND is_numeric($params["exclusive"])) $conditions["not"]["{$this->name}.id"]     =$params["exclusive"];

		$conditions["and"]["{$this->name}.del_flg"]=0;
		$this->__addRange($conditions,$start,$end);
		$data=$this->find("first",array("conditions"=>$conditions));
		return $data;
	}

	public function removeById($id)
	{
		$save=array();
		$save["id"]=$id;
		$save["del_flg"]=1;
		$this->id=$id;
		return $this->save($save);
	}

	public function getAllDataBasedonRange($start,$end,$params=array())
	{
		$conditions=array();
		if(isset($params["room_id"]))   $conditions["and"]["{$this->name}.room_id"]=$params["room_id"];
		if(isset($params["exclusive"]) AND is_numeric($params["exclusive"])) $conditions["not"]["{$this->name}.id"]     =$params["exclusive"];

		$format="";
		if(isset($params["format"])) $format=$params["format"];

		$conditions["and"]["{$this->name}.del_flg"]=0;
		$this->__addRange($conditions,$start,$end,$format);
		$data=$this->find("all",array("conditions"=>$conditions));
		return $data;
	}

}
