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
class AdK9DataReststaySchedule extends AppModel{

    var $name = "K9DataReststaySchedule";
    var $useTable = "k9_data_reststay_schedules";
    var $primaryKey = "id";
	var $useDbConfig="default";
	var $stayType="rest";

	public $belongsTo = array(

		'K9DataReservation' => array(

			'className'  => 'K9DataReservation',
			'foreignKey' => 'reserve_id',
			'conditions' => array('K9DataReservation.del_flg' => '0'),
			'dependent'  => false,
		),
	);

	public $hasMany = array(

		'K9DataDipositReststaySchedule' => array(

			'className'  => 'K9DataDipositReststaySchedule',
			'foreignKey' => 'schedule_id',
			'conditions' => array('K9DataDipositReststaySchedule.del_flg' => '0'),
		),
	);

	function scheduleByYm($start_date,$end_date,$params=array()){

		$w=null;
		$w["and"]["CONCAT({$this->name}.start_month_prefix,lpad({$this->name}.start_day,2,0)) between ? AND ?"]=array($start_date,$end_date);
		if(isset($params["del_flg"]) AND is_numeric($params["del_flg"])) $w["and"]["{$this->name}.del_flg"]=$params["del_flg"];

		$options=array();
		$options["conditions"]=$w;
		if(isset($params["recursive"])) $options["recursive"]=$params["recursive"];
		if(isset($params["order"]))     $options["order"]=$params["order"];
		//v($options);
		return $this->find("all",$options);
	}

	function scheduleByYmOtherTargetReservation($reserve_id,$start_date,$end_date,$params=array()){
	
		$w=null;
		if(!empty($reserve_id)) $w["not"]["reserve_id"]=$reserve_id;
		$w["and"]["CONCAT(start_month_prefix,lpad(start_day,2,0)) between ? AND ?"]=array($start_date,$end_date);
		if(isset($params["del_flg"]) AND is_numeric($params["del_flg"])) $w["and"]["{$this->name}.del_flg"]=$params["del_flg"];

		$options=array();
		$options["conditions"]=$w;
		if(isset($params["recursive"])) $options["recursive"]=$params["recursive"];
		return $this->find("all",$options);
	}

	function getSiteScheduleByDate($dates=array(),$del_flg=0){

		if(empty($dates)) return array();

		$w=array();
		$counter=0;
		foreach($dates as $k=>$v){

			$s=strtotime($v);
			$w["and"]["or"][$counter]["and"]["start_month_prefix"]=date("Ym",$s);
			$w["and"]["or"][$counter++]["and"]["start_day"]=date("j",$s);
		}

		if(is_numeric($del_flg)) $w["and"]["{$this->name}.del_flg"]=$del_flg;
		return $this->findAll($w);
	}

	function changeDeleteSituationScheduleById($schedule_id=array(),$del_flg=1){

		if(!is_array($schedule_id)) $schedule_id[]=$schedule_id;
		$query ="update {$this->useTable} set del_flg=\"{$del_flg}\"";
		$query.=" where id IN(".implode(",",$schedule_id).");";

		try{ $this->query($query);
		}catch(Exception $e){

			$res["message"]=$e->getMessage();
			$res["status"]=false;
			return $res;
		}

		$res["status"]=true;
		return $res;
	}

	function changeDeleteScheduleByReservationIdOverTheDay($reserve_id,$date,$del_flg=1){

		$query ="update {$this->useTable} set del_flg=\"{$del_flg}\"";
		$query.=" where reserve_id=\"{$reserve_id}\" AND CONCAT(start_month_prefix,lpad(start_day,2,0))>=\"{$date}\";";

		try{ $this->query($query);
		}catch(Exception $e){

			$res["message"]=$e->getMessage();
			$res["status"]=false;
			return $res;
		}

		$res["status"]=true;
		return $res;
	}

	public function getScheduleByDate($ymd,$params=array())
	{

		$ym=substr($ymd,0,6);
		$d =substr($ymd,6,2);

		$conditions["and"]["{$this->name}.start_month_prefix"]=$ym;
		$conditions["and"]["{$this->name}.start_day"]=(Int)$d;
		$conditions["and"]["{$this->name}.del_flg"]=0;

		$options=array();
		if(isset($params["recursive"])) $options["recursive"]=$params["recursive"];
		$options["conditions"]=$conditions;
		$data=$this->find("all",$options);
		return $data;
	}

	public function getUsedSchedules($ymd)
	{

		$conditionsSubQuery["CONCAT(start_month_prefix,lpad(start_day,2,0)) >= "]=$ymd;
		$conditionsSubQuery["del_flg"]=0;
		$dbo=$this->getDataSource();
		$subQuery=$dbo->buildStatement(array(

			'fields'=>array("`{$this->name}`.`id`"),
			'table'=>$dbo->fullTableName($this),
			'alias'=>$this->name,
			'limit'=>null,
			'offset'=>null,
			'joins'=>array(),
			'conditions'=>$conditionsSubQuery,
			'order'=>null,
			'group'=>null),

		$this);
		$subQuery="`{$this->name}`.`id` IN ({$subQuery})";
		$subQueryExpression=$dbo->expression($subQuery);
		$conditions[]=$subQueryExpression;
		$data=$this->find("all",array(
		
			"conditions"=>$conditions,
			"recursive" =>2
		));

		return $data;
	}
	
}
