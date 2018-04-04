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
class AdK9DataReservation extends AppModel{

    var $name = "K9DataReservation";
    var $useTable = "k9_data_reservations";
    var $primaryKey = "id";
	var $useDbConfig="default";

	public static $REST="rest";
	public static $STAY="stay";

	var $association=array(

		"hasOne"=>array(

			'K9DataCheckoutPayment' => array(

				'className' => 'K9DataCheckoutPayment',
				'foreignKey' => 'reserve_id',
				'conditions'=> array('K9DataCheckoutPayment.del_flg' => '0'),
			),
		),
		"hasMany"=>array(

			'K9DataReststaySchedule' => array(

				'className' => 'K9DataReststaySchedule',
				'foreignKey' => 'reserve_id'
			),
			'K9DataSchedule' => array(

				'className' => 'K9DataSchedule',
				'foreignKey' => 'reserve_id'
			),
			'K9DataSchedulePlan' => array(

				'className' => 'K9DataSchedulePlan',
				'foreignKey' => 'reserve_id'
			),
			'K9DataOrderSpa' => array(

				'className' => 'K9DataOrderSpa',
				'foreignKey' => 'reserve_id'
			),
			'K9DataOrderFood' => array(

				'className' => 'K9DataOrderFood',
				'foreignKey' => 'reserve_id'
			),
			'K9DataOrderBeverage' => array(

				'className' => 'K9DataOrderBeverage',
				'foreignKey' => 'reserve_id'
			),
			'K9DataOrderLimousine' => array(

				'className' => 'K9DataOrderLimousine',
				'foreignKey' => 'reserve_id'
			),
			'K9DataOrderLaundry' => array(

				'className' => 'K9DataOrderLaundry',
				'foreignKey' => 'reserve_id'
			),
			'K9DataOrderTobacco' => array(

				'className' => 'K9DataOrderTobacco',
				'foreignKey' => 'reserve_id'
			),
			'K9DataOrderRoomservice' => array(

				'className' => 'K9DataOrderRoomservice',
				'foreignKey' => 'reserve_id'
			),
		),
		"belongsTo"=>array(

			'K9DataCompany' => array(

				'className' => 'K9DataCompany',
				'foreignKey' => 'company_id'
			),
			'K9DataGuest' => array(

				'className' => 'K9DataGuest',
				'foreignKey' => 'guest_id'
			),
			'K9MasterCard' => array(

				'className' => 'K9MasterCard',
				'foreignKey' => 'cash_type_id'
			),
			'K9MasterEmployee' => array(
	
				'className' => 'K9MasterEmployee',
				'foreignKey' => 'final_employee_entered',
				'conditions' => array("K9MasterEmployee.del_flg"=>'0'),
			),
			'K9MasterReservationSalesource' => array(
	
				'className' => 'K9MasterReservationSalesource',
				'foreignKey' => 'salesource_id',
			),
		)
	);

	public $belongsTo = array(

		'K9DataGuest' => array(
			'className' => 'K9DataGuest',
			'foreignKey' => 'guest_id',
			'conditions' => array("K9DataGuest.del_flg"=>'0')
		),
	);

	function scheduleByYm($start_date,$end_date,$params=array()){

		$w=null;
		if(isset($params["del_flg"]) AND is_numeric($params["del_flg"])) $w["and"]["{$this->name}.del_flg"]=$params["del_flg"];

		$w["and"]["{$this->name}.del_flg"]=0;
		$options=array();
		$options["conditions"]=$w;
		if(isset($params["recursive"])) $options["recursive"]=$params["recursive"];
		return $this->find("all",$options);
	}

	function getReservationWhenFinal($data){

		$schedule=$this->association["hasMany"]["K9DataSchedule"];
		$schedule["conditions"]=array("K9DataSchedule.del_flg"=>'0');
		$schedule["order"]=array("K9DataSchedule.start_month_prefix DESC","K9DataSchedule.start_day DESC");
		$this->hasMany["K9DataSchedule"]=$schedule;

		$w=null;
		$w["and"]["{$this->name}.".(is_numeric($data)?"id":"hash")]=$data;
		$w["and"]["{$this->name}.del_flg"]=0;
		$data=$this->find("first",array(
		
			"conditions"=>$w,
		));

		return $data;
	}

	function getReservation($reserve_ids=array(),$del_flg=0,$params=array()){

		//$this->unbindFully();
		//$schedule=$this->association["hasMany"]["K9DataSchedule"];
		//$schedule["conditions"]=array("K9DataSchedule.del_flg"=>'0');
		//$this->hasMany["K9DataSchedule"]=$schedule;

		$conditions=null;
		$conditions["and"]["{$this->name}.id"]=$reserve_ids;
		if(is_numeric($del_flg)) $conditions["and"]["{$this->name}.del_flg"]=$del_flg;
		$recursive=isset($params["recursive"])?$params["recursive"]:1;
		$data=$this->find("all",array( "recursive"=>$recursive,"conditions"=>$conditions ));
		return $data;
	}
	
	function editLastUser($reserve_id,$employee_id){
	
		$save["id"]=$reserve_id;
		$save["edit_user_id"]=$employee_id;
		return $this->save($save);
	}

	function getReservationByHash($hash,$del_flg=0,$params=array()){

		$w=null;
		$w["and"]["{$this->name}.hash"]=$hash;
		if(is_numeric($del_flg)) $w["and"]["{$this->name}.del_flg"]=$del_flg;
		$recursive=isset($params["recursive"])?$params["recursive"]:1;
		$data=$this->find("first",array(
		
			"conditions"=>$w,
			"recursive" =>$recursive
		));
		return $data;
	}

	function getAllReserveInfomrationsByHash($hash,$del_flg=0,$params=array()){

		$schedule=$this->association["hasMany"]["K9DataSchedule"];
		$schedule["conditions"]=array("K9DataSchedule.del_flg"=>'0');
		$plan=$this->association["hasMany"]["K9DataSchedulePlan"];
		$plan["conditions"]=array("K9DataSchedulePlan.del_flg"=>'0');
		$plan["order"]=array("K9DataSchedulePlan.created ASC");
		$this->hasMany["K9DataSchedule"]=$schedule;
		$this->hasMany["K9DataSchedulePlan"]=$plan;

		$w=null;
		$w["and"]["{$this->name}.hash"]=$hash;
		if(is_numeric($del_flg)) $w["and"]["{$this->name}.del_flg"]=$del_flg;
		$recursive=isset($params["recursive"])?$params["recursive"]:1;
		$data=$this->find("all",array(
		
			"conditions"=>$w,
			"recursive" =>$recursive
		));
		return $data;
	}

	function checkout($reserve_id){
	
		$time=date("YmdHis");
		$save["checkout_time"]=$time;
		$save["id"]=$reserve_id;
		if(!$this->save($save)) return false;
		return $time;
	}

	function checkin($reserve_id){
	
		$time=date("YmdHis");
		$save["checkin_time"]=$time;
		$save["id"]=$reserve_id;
		if(!$this->save($save)) return false;
		return $time;
	}

	function checkOutReservations($reserve_ids=array())
	{

		$w=null;
		$now=date("YmdHis");
		if(!empty($reserve_ids)) $w["and"]["{$this->name}.id"]=$reserve_ids;
		$w["not"]["DATE_FORMAT({$this->name}.checkout_time,'%Y%m%d')"]="00000000";
		$w["and"]["DATE_FORMAT({$this->name}.checkout_time,'%Y%m%d') <= "]=$now;
		return $this->find("all",array(
		
			"conditions"=>$w
		));
	}

	public function saveInvoice($reserve_id)
	{

	    $ymdhis=date("YmdHis");
        $file=CLIENT."_{$reserve_id}_{$ymdhis}.xlsx";

		$save=array();
		$save["id"]=$reserve_id;
		$save["invoice_num"]=$file;
		if(!parent::save($save)) return false;
		return $file;
	}

	public function resetToDefaultCompanyId($agent_ids=array())
	{
		$space=" ";
		$query="update {$this->useTable} set company_id=DEFAULT(company_id)";
		$query.=$space;
		$query.="where company_id IN(".implode(",",$agent_ids).");";
		return $this->query($query);
	}

	public function deleteReserveByIds($reserve_ids=array()){

		try{

			$v=array("del_flg"=>1);
			$c=array("K9DataReservation.id"=>$reserve_ids);
			$this->updateAll($v,$c);

		}catch(Exception $e){

			$res["message"]=$e->getMessage();
			$res["status"] =false;
			return $res;
		}

		$res["status"]=true;
		return $res;
	}

}
