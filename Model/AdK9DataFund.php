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
class AdK9DataFund extends AppModel{

    var $name = "K9DataFund";
    var $useTable = "k9_data_fund";
    var $primaryKey = "id";
	var $useDbConfig="default";

	public static $CASH="cash";
	public static $BANK="bank";

	public $belongsTo = array(

		'K9MasterEmployee' => array(
	
			'className' => 'K9MasterEmployee',
			'foreignKey' => 'final_employee_entered',
			'conditions' => array("K9MasterEmployee.del_flg"=>'0'),
		),
	);

	public function getLastCashByType($type,$day)
	{

		$conditions=array();
		$conditions["and"]["{$this->name}.cash_type"]=$type;
		$conditions["and"]["{$this->name}.del_flg"]  =0;
		$data=$this->find("all",array(

			"conditions"=>$conditions,
			"order"     =>array("{$this->name}.enter_time DESC")
		));

		if(empty("data")) return array();

		$hit_data=array();
		foreach($data as $k=>$v){

			$check_ymd=date("Ymd",strtotime($v["K9DataFund"]["enter_time"]));
			if($check_ymd>$day)continue;
			$hit_data=$v;
			break;
		}

		return $hit_data;
	}

	public function getLastCashByCash($day)
	{

		return $this->getLastCashByType(self::$CASH,$day);
	}

	public function getLastCashByBank($day)
	{

		return $this->getLastCashByType(self::$BANK,$day);
	}

	public function getLastCashs($day)
	{

		$cash=$this->getLastCashByCash($day);
		$bank=$this->getLastCashByBank($day);

		$res=array();
		$res[self::$BANK]=empty($bank)?array():$bank;
		$res[self::$CASH]=empty($cash)?array():$cash;
		return $res;
	}

	public function getBalanceHistoriesWithRange($start="",$end="",$params=array())
	{

		$conditions=array();

		switch(true){
		
		case(!empty($start) AND empty($end)):

			$conditions["and"]["DATE_FORMAT({$this->name}.enter_time,'%Y%m%d') >= "]=$start;
			break;

		case(empty($start) AND !empty($end)):

			$conditions["and"]["DATE_FORMAT({$this->name}.enter_time,'%Y%m%d') <= "]=$end;
			break;

		case(!empty($start) AND !empty($end)):

			$conditions["and"]["DATE_FORMAT({$this->name}.enter_time,'%Y%m%d') between ? AND ?"]=array($start,$end);
			break;
		}

		$conditions["and"]["{$this->name}.del_flg"]=0;

		$order=array();
		if(isset($params["order"])) $order=$params["order"];
		return $this->find("all",array(
		
			"conditions"=>$conditions,
			"order"=>$order
		));
	}

	public function balanceEdit($id,$type,$value,$employee_id)
	{

		$save["id"]=$id;
		$save["cash_type"]=$type;
		$save["cash"]=$value;
		$save["enter_time"]=date("YmdHis");
		$save["final_employee_entered"]=$employee_id;
		return $this->save($save);
	}

	public function balanceEditOfCash($id,$value,$employee_id)
	{
		return $this->balanceEdit($id,self::$CASH,$value,$employee_id);
	}

	public function balanceEditOfBank($id,$value,$employee_id)
	{
		return $this->balanceEdit($id,self::$BANK,$value,$employee_id);
	}

	public function balanceSubscribe($type,$value,$employee_id)
	{

		$save["cash_type"]=$type;
		$save["cash"]=$value;
		$save["enter_time"]=date("YmdHis");
		$save["final_employee_entered"]=$employee_id;
		$this->id=null;
		return $this->save($save);
	}

	public function balanceSubscribeOfCash($value,$employee_id)
	{
		return $this->balanceSubscribe(self::$CASH,$value,$employee_id);
	}

	public function balanceSubscribeOfBank($value,$employee_id)
	{
		return $this->balanceSubscribe(self::$BANK,$value,$employee_id);
	}

}
