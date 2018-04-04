<?php

App::uses("K9PriceController","Controller");
App::uses("K9RestPriceController","Controller");
App::uses("K9SiteController","Controller");
App::uses("K9InvoiceController","Controller");
App::uses("K9BasePricesController","Controller");

class AdK9PriceSituationsController extends AppController {

	var $name = 'K9PriceSituations';
	var $uses = [

			"K9MasterSpa",
			"K9MasterRoom",
			"K9MasterRoomType",
			"K9MasterFood",
			"K9MasterBeverage",
			"K9MasterLimousine",
			"K9MasterLaundry",
			"K9MasterRoomservice",
			"K9MasterReststay",
			"K9MasterTobacco",
			"K9DataHistoryPriceSpa",
			"K9DataHistoryPriceLaundry",
			"K9DataHistoryPriceLimousine",
			"K9DataHistoryPriceFood",
			"K9DataHistoryPriceBeverage",
			"K9DataHistoryPriceRoom",
			"K9DataHistoryPriceRoomType",
			"K9DataHistoryPriceReststay",
			"K9DataHistoryPriceRoomservice",
			"K9DataHistoryPriceTobacco",
			"K9DataOrderBeverage",
			"K9DataOrderFood",
			"K9DataOrderLimousine",
			"K9DataOrderLaundry",
			"K9DataOrderSpa",
			"K9DataOrderRoomservice",
			"K9DataOrderTobacco",
			"K9DataReservation",
			"K9DataSchedule",
			"K9DataReststaySchedule"];


	public function beforeFilter() {

		parent::beforeFilter();
	}

	public function getPriceHistory()
	{
		if(!$this->isPostRequest()) exit;

		$post=$this->data;
		//$post=$this->__getTestPostData();
	
		//$post["reservation_hash"]="1db428ab4c2152d2cdd314f8861b72b7f8589f32a8b33b0e18c89a12a7bc91a8";
		$reservation_hash=$post["reservation_hash"];
		if(!$reservation_info=$this->__getReservation($reservation_hash)){
		
			$res["message"]=__("正常に処理が終了しませんでした");
			Output::__outputNo($res);
		}

		$staytype=!empty($reservation_info["K9DataSchedule"])?"stay":"rest";
		$staytype_model=$this->__stayTypeModel($staytype);
		$schedule_plans=$this->__getSchedulePlans(array($reservation_info["K9DataReservation"]["id"]));
		$schedules=$reservation_info[$staytype_model->name];

		$first_schedule=$schedules[0];
		$last_schedule =$schedules[count($schedules)-1];
		$start_date=$first_schedule["start_month_prefix"].sprintf("%02d",$first_schedule["start_day"]);
		$end_date  =$last_schedule["start_month_prefix"].sprintf("%02d",$last_schedule["start_day"]);

		$price_info=array();
		$price_info=$this->__getPrice(array($reservation_info["K9DataReservation"]["id"]),array(
		
			"start_date"=>$start_date,
			"end_date"  =>$end_date
		));

		$price_reststay_info=$this->__getReststayPrice(array(
		
			"start_date"=>$start_date,
			"end_date"  =>$end_date
		));
	
		//meals.
		$reserve_id=$reservation_info["K9DataReservation"]["id"];
		$drink_orders=$this->__invoiceOtherRecords(

			$this->K9MasterBeverage,
			$this->K9DataHistoryPriceBeverage,
			$this->K9DataOrderBeverage,
			$reserve_id);

		$food_orders=$this->__invoiceOtherRecords(

			$this->K9MasterFood,
			$this->K9DataHistoryPriceFood,
			$this->K9DataOrderFood,
			$reserve_id);

		$spa_orders=$this->__invoiceOtherRecords(

			$this->K9MasterSpa,
			$this->K9DataHistoryPriceSpa,
			$this->K9DataOrderSpa,
			$reserve_id);

		$laundry_orders=$this->__invoiceOtherRecords(

			$this->K9MasterLaundry,
			$this->K9DataHistoryPriceLaundry,
			$this->K9DataOrderLaundry,
			$reserve_id);

		$limousine_orders=$this->__invoiceOtherRecords(

			$this->K9MasterLimousine,
			$this->K9DataHistoryPriceLimousine,
			$this->K9DataOrderLimousine,
			$reserve_id);

		$roomservice_orders=$this->__invoiceOtherRecords(

			$this->K9MasterRoomservice,
			$this->K9DataHistoryPriceRoomservice,
			$this->K9DataOrderRoomservice,
			$reserve_id);

		$tobacco_orders=$this->__invoiceOtherRecords(

			$this->K9MasterTobacco,
			$this->K9DataHistoryPriceTobacco,
			$this->K9DataOrderTobacco,
			$reserve_id);

		$total_reservation_price=array();
		foreach($schedules as $k=>$k9_schedule){

			$k9_reservation=$reservation_info["K9DataReservation"];
			$k9_employee=$reservation_info["K9MasterEmployee"];
			$reserve_id=$k9_reservation["id"];
			$ymd=$k9_schedule["start_month_prefix"].sprintf("%02d",$k9_schedule["start_day"]);
			$schedule_id=$k9_schedule["id"];

			$__schedule_plans=$schedule_plans[$reserve_id];
			$k9_plans=$this->__getPainByYmd($schedule_plans[$reserve_id],$ymd);

			$room_id     =$k9_plans["room_id"];
			$room_type_id=$k9_plans["room_type_id"];
			$room_type   =$k9_plans["room_type"];
			$room_floor  =$k9_plans["room_floor"];
			$room_num    =$k9_plans["room_num"];

			switch($staytype){
			
			case("stay"):

				$priceinfo=$this->__getPriceParYmd($ymd,$price_info,array(

					"room_id"     =>$room_id,
					"room_type_id"=>$room_type_id,
				));

				$price  =$priceinfo["price"];
				$status =$priceinfo["status"];
				$data_id=$priceinfo["data_id"];
				break;

			case("rest"):

				$priceinfo=$this->__getRestPriceParYmd($ymd,$price_reststay_info);

				$price  =$priceinfo["price"];
				$status =K9RestPriceController::$PRICE_RESTSTAY;
				$data_id=$priceinfo["data_id"];
				break;
			}

			$this->__updateWithWeekdayOrWeekend($price,$status,$k9_reservation,$ymd);

			$total_reservation_price[$ymd]["room"]["room"]["room_id"]     =$room_id;
			$total_reservation_price[$ymd]["room"]["room"]["room_type"]   =$room_type;
			$total_reservation_price[$ymd]["room"]["room"]["room_floor"]  =$room_floor;
			$total_reservation_price[$ymd]["room"]["room"]["room_type_id"]=$room_type_id;
			$total_reservation_price[$ymd]["room"]["room"]["room_num"]    =$room_num;
			$total_reservation_price[$ymd]["room"]["price"]["price"]      =$price;
			$total_reservation_price[$ymd]["room"]["price"]["data_id"]    =$priceinfo["data_id"];
			$total_reservation_price[$ymd]["room"]["price"]["par"]        =isset($priceinfo["par"])?$priceinfo["par"]:0;
			$total_reservation_price[$ymd]["room"]["price"]["room_price_status"]=$status;
			$total_reservation_price[$ymd]["room"]["employee"]["id"]  =$k9_employee["id"];
			$total_reservation_price[$ymd]["room"]["employee"]["name"]=$k9_employee["first_name"];

			if(isset($food_orders[$ymd])){

				$this->__addOrderForTotal(K9MasterFood::$CATEGORY_FOOD,$ymd,$food_orders,$total_reservation_price);
			}

			if(isset($drink_orders[$ymd])){

				$this->__addOrderForTotal(K9MasterBeverage::$CATEGORY_DRINK,$ymd,$drink_orders,$total_reservation_price);
			}

			if(isset($laundry_orders[$ymd])){

				$this->__addOrderForTotal(K9MasterLaundry::$CATEGORY_LAUNDRY,$ymd,$laundry_orders,$total_reservation_price);
			}

			if(isset($limousine_orders[$ymd])){

				$this->__addOrderForTotal(K9MasterLimousine::$CATEGORY_LIMOUSINE,$ymd,$limousine_orders,$total_reservation_price);
			}

			if(isset($spa_orders[$ymd])){

				$this->__addOrderForTotal(K9MasterSpa::$CATEGORY_SPA,$ymd,$spa_orders,$total_reservation_price);
			}

			if(isset($roomservice_orders[$ymd])){

				$this->__addOrderForTotal(K9MasterRoomservice::$CATEGORY_ROOMSERVICE,$ymd,$roomservice_orders,$total_reservation_price);
			}

			if(isset($tobacco_orders[$ymd])){

				$this->__addOrderForTotal(K9MasterTobacco::$CATEGORY_TOBACCO,$ymd,$tobacco_orders,$total_reservation_price);
			}
		}

		$guest=$reservation_info["K9DataGuest"];
		$res["data"]["information"]=$total_reservation_price;
		$res["data"]["guest"]["first_name"]=$guest["first_name"];
		$res["data"]["guest"]["middle_name"]=$guest["middle_name"];
		$res["data"]["guest"]["last_name"]=$guest["last_name"];
		$res["data"]["guest"]["remarks"]=$guest["remarks"];
		//$res["data"]["price"]["priority_price"]=$k9_reservation["priority_price"];
		Output::__outputYes($res);
	}

	function __addOrderForTotal($type,$ymd,$orders,&$total_reservation_price){

		if(!isset($total_reservation_price[$ymd][$type])) $total_reservation_price[$ymd][$type]=array();
	
		$counter=0;
		$day_orders=$orders[$ymd];
		foreach($day_orders as $k=>$order){

			$total_reservation_price[$ymd][$type][$counter]["price"]["count"]=$order["value"]["count"];
			$total_reservation_price[$ymd][$type][$counter]["price"]["price"]=$order["price"]["value"]*$order["value"]["count"];
			$total_reservation_price[$ymd][$type][$counter]["meal"]["name"]  =$order["value"]["name"];
			$total_reservation_price[$ymd][$type][$counter]["meal"]["id"]    =$order["value"]["id"];
			$total_reservation_price[$ymd][$type][$counter]["meal"]["remarks"]=$order["value"]["remarks"];
			$total_reservation_price[$ymd][$type][$counter]["category"]["name"]=$order["category"]["name"];
			$total_reservation_price[$ymd][$type][$counter]["employee"]["id"]=$order["employee"]["id"];
			$total_reservation_price[$ymd][$type][$counter]["employee"]["name"]=$order["employee"]["name"];
			$counter++;
		}
	}

	function __getReservation($reservation_hash){

		$data=$this->__getReservationByHash($reservation_hash);
		if(empty($data["K9DataSchedule"]) AND empty($data["K9DataReststaySchedule"])) throw new Exception(__("正常に処理が終了しませんでした"));
		return $data;
	}

	function __getReservationByHash($hash){

		$association=$this->K9DataReservation->association;
		$schedule=$association["hasMany"]["K9DataSchedule"];
		$schedule["conditions"]=array("K9DataSchedule.del_flg"=>'0');
		$schedule["order"]=array("K9DataSchedule.start_month_prefix ASC","K9DataSchedule.start_day ASC");

		$schedule_rest=$association["hasMany"]["K9DataReststaySchedule"];
		$schedule_rest["conditions"]=array("K9DataReststaySchedule.del_flg"=>'0');
		$schedule_rest["order"]=array("K9DataReststaySchedule.start_month_prefix ASC","K9DataReststaySchedule.start_day ASC");

		$employee=$association["belongsTo"]["K9MasterEmployee"];
		$this->K9DataReservation->bindModel(array("belongsTo"=>array("K9MasterEmployee"=>$employee)));
		$this->K9DataReservation->bindModel(array("hasMany"=>array("K9DataSchedule"=>$schedule,"K9DataReststaySchedule"=>$schedule_rest)));
		$data=$this->K9DataReservation->getReservationByHash($hash,0);
		return $data;
	}

	function __getPrice($reserve_ids=array(),$params=array()){

		$controller=new K9PriceController();
		$res=$controller->__getPrice($reserve_ids,$params);
		return $res;
	}

	function __getReststayPrice($params=array()){

		$controller=new K9RestPriceController();
		$res=$controller->__getReststayPrice($params);
		return $res;
	}
	
	function __getSchedulePlans($reserve_ids=array()){

		$controller=new K9SiteController();
		$res=$controller->__getSchedulePlans($reserve_ids);
		return $res;
	}

	function __getPainByYmd($data,$check_ymd){

		$controller=new K9SiteController();
		$res=$controller->__getPainByYmd($data,$check_ymd);
		return $res;
	}

	function __getPriceParYmd($ymd,$price_info,$params=array()){

		$controller=new K9SiteController();
		$res=$controller->__getPriceParYmd($ymd,$price_info,$params);
		return $res;
	}

	function __updateWithWeekdayOrWeekend(&$price,&$status,$k9_reservation,$ymd){

		$controller=new K9SiteController();
		$res=$controller->__updateWithWeekdayOrWeekend($price,$status,$k9_reservation,$ymd);
		return $res;
	}

	function __getRestPriceParYmd($ymd,$price_info,$params=array()){

		$controller=new K9SiteController();
		$res=$controller->__getRestPriceParYmd($ymd,$price_info,$params);
		return $res;
	}

	function __invoiceOtherRecords(Model $master_model,Model $history_model,Model $order_model,$reserve_id){

		$controller=new K9InvoiceController();
		$res=$controller->__invoiceOtherRecords($master_model,$history_model,$order_model,$reserve_id);
		return $res;
	}

}
