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
class AdK9DataExtraOrder extends AppModel{

    var $name = "K9DataExtraOrder";
    var $useTable = "k9_data_extra_orders";
    var $primaryKey = "id";
	var $useDbConfig="default";

	public $hasMany = array(

		'K9DataExtraRoomserviceOrder' => array(

			'className' => 'K9DataExtraRoomserviceOrder',
			'foreignKey' => 'group_id',
		),
		'K9DataExtraFoodOrder' => array(

			'className' => 'K9DataExtraFoodOrder',
			'foreignKey' => 'group_id',
		),
		'K9DataExtraBeverageOrder' => array(

			'className' => 'K9DataExtraBeverageOrder',
			'foreignKey' => 'group_id',
		),
		'K9DataExtraTobaccoOrder' => array(

			'className' => 'K9DataExtraTobaccoOrder',
			'foreignKey' => 'group_id',
		),
	);

	public function firstInsert($ymd)
	{

		$save=array();
		$save["target_date"]=$ymd;
		$save["regist_date"]=date("YmdHis");
		$res=$this->save($save);
		if(empty($res)) return false;
		return $this->getLastInsertID();
	}

	public function getExtraOrder($day="",$params=array())
	{
		$conditions=array();
		if(isYmd($day)) $conditions["and"]["DATE_FORMAT(K9DataExtraOrder.target_date,'%Y%m%d')"]=$day;
		return $this->find("all",array(
		
			"conditions"=>$conditions,
			"recursive" =>(isset($params["recursive"]) AND is_numeric($params["recursive"]))?$params["recursive"]:1
		));
	}

	public function saveInvoiceNum($order_id,$prefix="")
	{
		$time=date("His");
		if(empty($prefix)) $prefix=CLIENT;
		$invoice_num="{$prefix}{$order_id}_{$time}";

		$this->id=$order_id;
		$save["id"]=$order_id;
		$save["latest_invoice_num"]=$invoice_num;
		if(!$this->save($save)) return false;
		return $invoice_num;
	}

}
