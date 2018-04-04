<?php

App::uses('K9DailyReportHotelPricesController','Controller');
App::uses('K9DailyReportOrderHistoriesController','Controller');
App::uses('K9BasePricesController','Controller');

class AdK9TotalCostsController extends AppController{

	var $name = "K9TotalCosts";
    var $uses = ["K9DataSchedule","K9DataReststaySchedule"];

	function beforeFilter(){

		parent::beforeFilter();
	}

	public function __getTotalCost($reserve_id,$staytype)
	{

		$schedule_model=$this->__stayTypeModel($staytype);
		$schedules=$this->__getSchedules($schedule_model,$reserve_id);

		$schedule_days=array();
		foreach($schedules as $k=>$v) $schedule_days[]=makeYmdByYmAndD($v[$schedule_model->name]["start_month_prefix"],$v[$schedule_model->name]["start_day"]);
		$hotel_total_price=$this->__hotelPrices($reserve_id,$schedule_days[0]);
		$order_total_price=$this->__orderHistory($schedule_days,$reserve_id);

		$res=array();
		$res["hotel"]=$hotel_total_price["price"];
		$res["order"]=$order_total_price;
		return $res;
	}

	private function __getSchedules(Model $schedule_model,$reserve_id)
	{

		$conditions=array();
		$conditions["and"]["{$schedule_model->name}.reserve_id"]=$reserve_id;
		$conditions["and"]["{$schedule_model->name}.del_flg"]   =0;

		$schedule_model->unbindFully();
		$schedules=$schedule_model->find("all",array(
		
			"conditions"=>$conditions,
			"order"     =>array("{$schedule_model->name}.start_month_prefix ASC","{$schedule_model->name}.start_day ASC")
		));

		return $schedules;
	}

	private function __orderHistory($days,$reserve_id){

		$controller=new K9DailyReportOrderHistoriesController();
		$controller->__setOrderModels();
		$res=$controller->__orderHistory($days,$reserve_id);
		if(empty($res)) return 0;

		$values=$this->__culcOrderPrice($res);

		$prices=array("cash"=>0,"card"=>0);
		foreach($values as $date=>$v){

			foreach($v as $category=>$_v){
			
				$prices["cash"]+=isset($_v["cash"])?$_v["cash"]:0;
				$prices["card"]+=isset($_v["card"])?$_v["card"]:0;
			}
		}

		return $prices;
	}

	private function __culcOrderPrice($data,$values=array())
	{

		if(empty($data)) return $values;
		foreach($data as $date=>$v) break;
		unset($data[$date]);
		$values[$date]=$this->__culcOrderEachPrice($v);
		return $this->__culcOrderPrice($data,$values);
	}

	private function __culcOrderEachPrice($data,$values=array())
	{

		if(empty($data)) return $values;
		foreach($data as $type=>$v) break;
		unset($data[$type]);

		if(!isset($values[$type])) $values[$type]=array();

		switch(true){

		case(in_array($type,array(K9MasterFood::$CATEGORY_FOOD,K9MasterTobacco::$CATEGORY_TOBACCO))):

			foreach($v as $category=>$_v){

				foreach($_v as $cash_type=>$__v){

					if(!isset($values[$type][$cash_type])) $values[$type][$cash_type]=0;
					foreach($__v as $___k=>$___v) $values[$type][$___v["cash"]["type"]]+=$___v["price"];
				}
			}
			break;

		default:

			foreach($v as $cash_type=>$v){

				if(!isset($values[$type][$cash_type])) $values[$type][$cash_type]=0;
				foreach($v as $_k=>$_v) $values[$type][$_v["cash"]["type"]]+=$_v["price"];
			}
			break;
		}

		return $this->__culcOrderEachPrice($data,$values);
	}

	private function __hotelPrices($reserve_ids,$day){

		$controller=new K9DailyReportHotelPricesController();
		$value=$controller->__hotelPrices($reserve_ids,$day);

		$res=array();
		$res["price"]["cash"]=isset($value["cash"]["price"])?$value["cash"]["price"]:0;
		$res["price"]["card"]=isset($value["card"]["price"])?$value["card"]["price"]:0;
		return $res;
	}

}//END class

?>
