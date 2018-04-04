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
class AdK9MasterCard extends AppModel{

    var $name = "K9MasterCard";
    var $useTable = "k9_master_cards";
    var $primaryKey = "id";
	var $useDbConfig="default";

	var $association=array(
	
		"hasOne"=>array(

			'K9DataHistoryPriceCard' => array(
	
				'className'  => 'K9DataHistoryPriceCard',
				'foreignKey' => 'card_id',
			),
		),
	);

	public static $CASHTYPE_CASH=1;
	public static $CASHTYPE_VISA=2;
	public static $CASHTYPE_MASTER=3;
	public static $CASHTYPE_JCB=4;
	public static $CASHTYPE_UNIONPAY=5;

	public static $CARD="card";
	public static $CASH="cash";

	function getCards($del_flg=0,$params=array()){
	
		$order=array();
		$conditions=array();
		if(is_numeric($del_flg)) $conditions["and"]["{$this->name}.del_flg"]=$del_flg;
		if(isset($params["order"])) $order=array("{$this->name}.order_num {$params["order"]}");
		if(isset($params["type"]))  $conditions["and"]["{$this->name}.type"]=$params["type"];

		$recursive=isset($params["recursive"])?$params["recursive"]:1;
		return $this->find("all",array(
		
			"conditions"=>$conditions,
			"order"     =>$order,
			"recursive" =>$recursive
		));
	}

	public function getInformationOfCard($card_id=null)
	{
		return $this->getInformationByType(self::$CARD,$card_id);
	}

	public function getInformationOfCash($card_id=null)
	{
		return $this->getInformationByType(self::$CASH,$card_id);
	}

	public function getInformationByType($type,$card_id)
	{

		$conditions=array();

		$allow_types[]=self::$CASHTYPE_VISA;
		$allow_types[]=self::$CASHTYPE_MASTER;
		$allow_types[]=self::$CASHTYPE_JCB;
		$allow_types[]=self::$CASHTYPE_UNIONPAY;
		if(!empty($card_id) AND in_array($card_id,$allow_types)) $conditions["and"]["{$this->name}.id"]=$card_id;
		if(in_array($type,array(self::$CARD,self::$CASH))) $conditions["and"]["{$this->name}.type"]=$type;

		$conditions["and"]["{$this->name}.del_flg"]=0;
		return $this->find("all",array(
		
			"conditions"=>$conditions
		));
	}



}
