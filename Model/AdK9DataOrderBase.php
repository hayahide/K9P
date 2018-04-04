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

class AdK9DataOrderBase extends AppModel{

    var $primaryKey = "id";
	var $useDbConfig="default";

	public function getHistories($reserve_id,$date,$params=array())
	{

		$w=null;
		$w["and"]["{$this->name}.reserve_id"]=$reserve_id;
		if(!empty($date)) $w["and"]["DATE_FORMAT({$this->name}.created,'%Y%m%d')"]=$date;
		if(isset($params["count"]) AND is_numeric($params["count"])) $w["and"]["{$this->name}.count >= "]=$params["count"];
		$params["conditions"]=$w;
		return $this->find("all",$params);
	}

	public function getHistoryByDate($ymd,$params=array())
	{

		$conditions=array();

		// added 2018/02/13
		if(is_numeric($params["reserve_id"])) $conditions["and"]["{$this->name}.reserve_id"]=$params["reserve_id"];
		$conditions["and"]["DATE_FORMAT({$this->name}.created,'%Y%m%d')"]=$ymd;
		if(isset($params["count"]) AND is_numeric($params["count"])) $w["and"]["{$this->name}.count >= "]=$params["count"];

		$options=array();
		if(isset($params["recursive"])) $options["recursive"]=$params["recursive"];
		$params["conditions"]=$conditions;
		$data=$this->find("all",$params);
		return $data;
	}

	public function getOrderAfterEntertime($date,$params=array())
	{

		$ymdhis=date("YmdHis",strtotime($date));

		$conditions=array();
		$conditions["and"]["DATE_FORMAT({$this->name}.enter_time,'%Y%m%d%H%i%s') > "]=$ymdhis;
		if(isset($params["count"]) AND is_numeric($params["count"])) $w["and"]["{$this->name}.count >= "]=$params["count"];

		$recursive=isset($params["recursive"])?$params["recursive"]:1;
		$data=$this->find("all",array(
		
			"conditions"=>$conditions,
			"recursive" =>$recursive
		));

		return $data;
	}

	public function getOrderByCardtypeWithBeforeDate($card_id,$day,$params=array())
	{
		//created == schedule/start_month_prefix + start_day 
		$conditions=array();
		$conditions["and"]["{$this->name}.cash_type_id"]=$card_id;
		$recursive=isset($params["recursive"])?$params["recursive"]:1;
		$conditions["and"]["DATE_FORMAT({$this->name}.created,'%Y%m%d')"]=$day;
		if(isset($params["count"]) AND is_numeric($params["count"])) $w["and"]["{$this->name}.count >= "]=$params["count"];

		$data=$this->find("all",array(
		
			"conditions"=>$conditions,
			"recursive" =>$recursive
		));

		return $data;
	}

}
